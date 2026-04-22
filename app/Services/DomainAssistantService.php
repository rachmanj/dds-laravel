<?php

namespace App\Services;

use App\Models\User;
use Generator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\StreamInterface;

class DomainAssistantService
{
    private const SESSION_KEY = 'domain_assistant.messages';

    private const MAX_HISTORY_MESSAGES = 20;

    private const MAX_TOOL_ROUNDS = 6;

    /** @var list<string> */
    private array $lastToolsInvoked = [];

    public function __construct(
        private DomainAssistantDataService $data,
    ) {}

    public function isConfigured(): bool
    {
        return filled(config('services.openrouter.key'));
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{history: list<array{role: string, content: string}>, reply: string, tools_invoked: list<string>}
     */
    public function appendUserMessageAndComplete(User $user, string $userMessage, array $history, bool $showAllRecords = false): array
    {
        $this->lastToolsInvoked = [];
        $history[] = ['role' => 'user', 'content' => $userMessage];
        $reply = $this->completeChat($user, $history, $showAllRecords);
        $history[] = ['role' => 'assistant', 'content' => $reply];

        return [
            'history' => array_slice($history, -self::MAX_HISTORY_MESSAGES),
            'reply' => $reply,
            'tools_invoked' => $this->lastToolsInvoked,
        ];
    }

    public static function sessionKey(): string
    {
        return self::SESSION_KEY;
    }

    /**
     * @param  list<array{role: string, content: string}>  $conversationMessages
     */
    public function completeChat(User $user, array $conversationMessages, bool $showAllRecords = false): string
    {
        $this->lastToolsInvoked = [];
        if (! config('services.domain_assistant.tools_enabled', true)) {
            return $this->completeChatWithoutTools($user, $conversationMessages, $showAllRecords);
        }

        return $this->completeChatWithTools($user, $conversationMessages, $showAllRecords);
    }

    /**
     * Stream assistant text deltas (OpenRouter SSE). Only valid when tools are disabled for this deployment.
     *
     * @param  list<array{role: string, content: string}>  $conversationMessages
     * @return Generator<string>
     */
    public function streamCompleteChatWithoutTools(User $user, array $conversationMessages, bool $showAllRecords): Generator
    {
        $this->lastToolsInvoked = [];
        $messages = array_merge(
            [['role' => 'system', 'content' => $this->systemPrompt($user, $showAllRecords)]],
            $conversationMessages
        );

        yield from $this->streamChatCompletion($messages);
    }

    /**
     * @param  list<array{role: string, content: string}>  $conversationMessages
     */
    private function completeChatWithoutTools(User $user, array $conversationMessages, bool $showAllRecords): string
    {
        $messages = array_merge(
            [['role' => 'system', 'content' => $this->systemPrompt($user, $showAllRecords)]],
            $conversationMessages
        );

        $body = $this->postChatCompletion($messages, null);

        return $this->extractAssistantText($body)
            ?? throw new \RuntimeException('Unexpected OpenRouter response shape.');
    }

    /**
     * @param  list<array{role: string, content: string}>  $conversationMessages
     */
    private function completeChatWithTools(User $user, array $conversationMessages, bool $showAllRecords): string
    {
        $messages = array_merge(
            [['role' => 'system', 'content' => $this->systemPrompt($user, $showAllRecords)]],
            $conversationMessages
        );

        $tools = $this->toolDefinitions();

        for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
            $body = $this->postChatCompletion($messages, $tools);
            $choice = $body['choices'][0] ?? [];
            $message = $choice['message'] ?? [];

            $toolCalls = $message['tool_calls'] ?? null;
            if (is_array($toolCalls) && $toolCalls !== []) {
                $messages[] = $message;

                foreach ($toolCalls as $toolCall) {
                    $id = $toolCall['id'] ?? '';
                    $function = $toolCall['function'] ?? [];
                    $name = is_array($function) ? ($function['name'] ?? '') : '';
                    $arguments = is_array($function) ? ($function['arguments'] ?? '{}') : '{}';
                    if (! is_string($arguments)) {
                        $arguments = '{}';
                    }
                    $args = [];
                    $decoded = json_decode($arguments, true);
                    if (is_array($decoded)) {
                        $args = $decoded;
                    }

                    $result = $this->executeTool((string) $name, $args, $user, $showAllRecords);
                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => (string) $id,
                        'content' => $result,
                    ];
                }

                continue;
            }

            $text = $this->extractAssistantTextFromMessage($message);
            if ($text !== null && $text !== '') {
                return $text;
            }

            throw new \RuntimeException('Unexpected OpenRouter response: no text and no tool calls.');
        }

        throw new \RuntimeException('Tool loop limit reached.');
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function extractAssistantTextFromMessage(array $message): ?string
    {
        $content = $message['content'] ?? '';
        if (is_string($content) && $content !== '') {
            return $content;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function extractAssistantText(array $body): ?string
    {
        $message = $body['choices'][0]['message'] ?? [];

        return $this->extractAssistantTextFromMessage(is_array($message) ? $message : []);
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>|null  $tools
     * @return array<string, mixed>
     */
    private function postChatCompletion(array $messages, ?array $tools): array
    {
        $key = config('services.openrouter.key');
        if (! $key) {
            throw new \RuntimeException('OpenRouter API key is not configured.');
        }

        $model = config('services.openrouter.chat_model')
            ?: config('services.openrouter.model');

        $payload = [
            'model' => $model,
            'temperature' => 0.3,
            'messages' => $messages,
        ];

        if ($tools !== null) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $baseUrl = rtrim((string) config('services.openrouter.base_url'), '/');
        $url = $baseUrl.'/chat/completions';

        $timeout = (int) config('services.openrouter.chat_timeout', 120);

        $pending = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer '.$key,
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name', 'DDS'),
            ]);

        $pending = $this->applyTlsOptions($pending);

        $response = $pending->acceptJson()->post($url, $payload);

        if (! $response->successful()) {
            Log::warning('Domain assistant OpenRouter HTTP error', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            throw new \RuntimeException('OpenRouter request failed: '.$response->status());
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new \RuntimeException('Invalid OpenRouter JSON response.');
        }

        return $json;
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return Generator<string>
     */
    private function streamChatCompletion(array $messages): Generator
    {
        $key = config('services.openrouter.key');
        if (! $key) {
            throw new \RuntimeException('OpenRouter API key is not configured.');
        }

        $model = config('services.openrouter.chat_model')
            ?: config('services.openrouter.model');

        $payload = [
            'model' => $model,
            'temperature' => 0.3,
            'messages' => $messages,
            'stream' => true,
        ];

        $baseUrl = rtrim((string) config('services.openrouter.base_url'), '/');
        $url = $baseUrl.'/chat/completions';
        $timeout = (int) config('services.openrouter.chat_timeout', 120);

        $pending = Http::timeout($timeout)
            ->withHeaders([
                'Authorization' => 'Bearer '.$key,
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name', 'DDS'),
                'Accept' => 'text/event-stream',
            ]);

        $pending = $this->applyTlsOptions($pending);

        $response = $pending->withOptions(['stream' => true])->post($url, $payload);

        if (! $response->successful()) {
            Log::warning('Domain assistant OpenRouter stream HTTP error', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);
            throw new \RuntimeException('OpenRouter stream failed: '.$response->status());
        }

        $body = $response->getBody();
        yield from $this->parseSseContentDeltas($body);
    }

    /**
     * @return Generator<string>
     */
    private function parseSseContentDeltas(StreamInterface $body): Generator
    {
        $buffer = '';
        while (! $body->eof()) {
            $buffer .= $body->read(1024);
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);
                if ($line === '' || $line === 'data: [DONE]') {
                    continue;
                }
                if (! str_starts_with($line, 'data: ')) {
                    continue;
                }
                $json = substr($line, 6);
                $data = json_decode($json, true);
                if (! is_array($data)) {
                    continue;
                }
                $delta = $data['choices'][0]['delta']['content'] ?? '';
                if (is_string($delta) && $delta !== '') {
                    yield $delta;
                }
            }
        }
    }

    private function applyTlsOptions(\Illuminate\Http\Client\PendingRequest $pending): \Illuminate\Http\Client\PendingRequest
    {
        $caBundle = config('services.openrouter.ca_bundle');
        if (filled($caBundle) && is_string($caBundle) && is_file($caBundle)) {
            return $pending->withOptions(['verify' => $caBundle]);
        }

        return $pending;
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function executeTool(string $name, array $args, User $user, bool $showAllRecords): string
    {
        $this->lastToolsInvoked[] = $name;
        [$dateFrom, $dateTo] = $this->toolDateBounds($args);

        try {
            return match ($name) {
                'get_domain_summary' => json_encode($this->data->getDomainSummary($user, $showAllRecords)),
                'search_invoices' => json_encode($this->data->searchInvoices(
                    $user,
                    $showAllRecords,
                    isset($args['status']) && is_string($args['status']) ? $args['status'] : null,
                    isset($args['limit']) ? (int) $args['limit'] : 10,
                    $dateFrom,
                    $dateTo,
                    isset($args['supplier_query']) && is_string($args['supplier_query']) ? $args['supplier_query'] : null,
                    isset($args['invoice_number_query']) && is_string($args['invoice_number_query']) ? $args['invoice_number_query'] : null,
                )),
                'search_additional_documents' => json_encode($this->data->searchAdditionalDocuments(
                    $user,
                    $showAllRecords,
                    isset($args['status']) && is_string($args['status']) ? $args['status'] : null,
                    isset($args['limit']) ? (int) $args['limit'] : 10,
                    $dateFrom,
                    $dateTo
                )),
                'search_distributions' => json_encode($this->data->searchDistributions(
                    $user,
                    isset($args['status']) && is_string($args['status']) ? $args['status'] : null,
                    isset($args['limit']) ? (int) $args['limit'] : 10,
                    $dateFrom,
                    $dateTo
                )),
                'search_reconcile_records' => json_encode($this->data->searchReconcileRecords(
                    $user,
                    isset($args['invoice_no']) && is_string($args['invoice_no']) ? $args['invoice_no'] : null,
                    isset($args['limit']) ? (int) $args['limit'] : 10,
                    $dateFrom,
                    $dateTo
                )),
                'search_suppliers' => json_encode($this->data->searchSuppliers(
                    $user,
                    isset($args['query']) && is_string($args['query']) ? $args['query'] : null,
                    isset($args['limit']) ? (int) $args['limit'] : 10
                )),
                'get_active_solar_unit_price' => json_encode($this->data->getActiveSolarUnitPrice(
                    $user,
                    isset($args['reference_date']) && is_string($args['reference_date']) ? $args['reference_date'] : null
                )),
                default => json_encode(['error' => 'Unknown tool: '.$name]),
            };
        } catch (\Throwable $e) {
            report($e);

            return json_encode(['error' => 'Tool execution failed.']);
        }
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: ?string, 1: ?string}
     */
    private function toolDateBounds(array $args): array
    {
        return [
            isset($args['date_from']) && is_string($args['date_from']) ? $args['date_from'] : null,
            isset($args['date_to']) && is_string($args['date_to']) ? $args['date_to'] : null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function toolDefinitions(): array
    {
        $emptyObject = new \stdClass;

        $dateProps = [
            'date_from' => [
                'type' => 'string',
                'description' => 'Optional start date YYYY-MM-DD (inclusive). With date_to, span must be ≤ 90 days.',
            ],
            'date_to' => [
                'type' => 'string',
                'description' => 'Optional end date YYYY-MM-DD (inclusive).',
            ],
        ];

        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_domain_summary',
                    'description' => 'Returns permission-filtered counts: invoices, additional documents, distributions, reconcile rows (same user scope as the reconcile report), and active suppliers. Invoice/additional-document scope follows list rules and the assistant “show all records” toggle when permitted.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $emptyObject,
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_invoices',
                    'description' => 'Recent invoices the user may see (newest first). When the user gives a specific invoice number, faktur (tax invoice) number, or PO fragment (e.g. 26-03046, SG410-00000129), pass it as invoice_number_query — substring match on invoice_number, faktur_no, and po_no. When the user names a vendor/supplier/company (any language, e.g. Indonesian “dari …”, “untuk …”), pass supplier_query with that name (or distinctive substring). Optional workflow status. Optional date_from/date_to on invoice_date (falls back to created_at when invoice_date is null). Max 90-day window.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => array_merge([
                            'invoice_number_query' => [
                                'type' => 'string',
                                'description' => 'Substring to match against invoice_number, faktur_no, or po_no when the user asks for a specific document reference. Use this (not supplier_query) for numeric or alphanumeric invoice/faktur/PO identifiers.',
                            ],
                            'supplier_query' => [
                                'type' => 'string',
                                'description' => 'Filter by supplier: substring match on supplier name or SAP code (same idea as supplier search). Required when the user asks for invoices from/for a specific vendor.',
                            ],
                            'status' => [
                                'type' => 'string',
                                'description' => 'Optional exact invoices.status value.',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Max rows 1–20, default 10.',
                            ],
                        ], $dateProps),
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_additional_documents',
                    'description' => 'Recent additional documents the user may see. Optional status. Optional date range on document_date (falls back to created_at when null). Max 90-day window.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => array_merge([
                            'status' => [
                                'type' => 'string',
                                'description' => 'Optional additional_documents.status filter.',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Max rows 1–20, default 10.',
                            ],
                        ], $dateProps),
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_distributions',
                    'description' => 'Recent distributions visible to the user. Optional status. Optional date range on created_at. Max 90-day window.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => array_merge([
                            'status' => [
                                'type' => 'string',
                                'description' => 'Optional distributions.status (e.g. draft, sent).',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Max rows 1–20, default 10.',
                            ],
                        ], $dateProps),
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_reconcile_records',
                    'description' => 'Reconcile rows for the current user only (forUser + withoutFlag). Optional invoice_no substring. Optional created_at date range. Max 90-day window.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => array_merge([
                            'invoice_no' => [
                                'type' => 'string',
                                'description' => 'Optional substring on reconcile invoice_no.',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Max rows 1–20, default 10.',
                            ],
                        ], $dateProps),
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_suppliers',
                    'description' => 'Search active suppliers by name or SAP code. Use multiple words (e.g. company name) to narrow results — all words must match. Single token that looks like an SAP/vendor code matches exact sap_code (case-insensitive) and substring. Omit query for first N alphabetically. No date filter.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'Substring for name or sap_code.',
                            ],
                            'limit' => [
                                'type' => 'integer',
                                'description' => 'Max rows 1–20, default 10.',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_active_solar_unit_price',
                    'description' => 'Get the current PERTAMINA solar (BIOSOLAR / harga solar pinjaman) **unit price in IDR** and its **applicable period** from approved solar price history. Use when the user asks (in Indonesian or English) for the current solar price, harga solar pinjaman, harga solar hari ini, or which period the price applies to. Optional reference_date to ask “what was the price on that day” (YYYY-MM-DD).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'reference_date' => [
                                'type' => 'string',
                                'description' => 'Optional. Calendar day YYYY-MM-DD to resolve which period was active. Omit for “now” (today, app timezone).',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function systemPrompt(User $user, bool $showAllRecords): string
    {
        $name = $user->name ?? 'User';
        $expand = $showAllRecords && $user->can('see-all-record-switch');
        $scopeLine = $expand
            ? 'This request uses EXPANDED list scope for invoices and additional documents (same as “Show all records” on list pages when the user has see-all-record-switch).'
            : 'This request uses DEFAULT list scope for invoices and additional documents (same as list pages with “Show all records” OFF for users who are not accounting/finance/admin/superadmin).';

        return <<<PROMPT
You are the ARKA DDS domain assistant for {$name}. The DDS application covers additional documents, invoices, distributions, reconcile imports, suppliers, SAP-related workflows, processing analytics, and internal messaging.

{$scopeLine}

When the user asks for live or numeric information, call tools. Optional date_from/date_to on search tools must be YYYY-MM-DD and span at most 90 days. Never invent database values.

For a specific invoice, faktur, or PO reference, call search_invoices with invoice_number_query set to that fragment. For invoices tied to a named supplier or vendor, call search_invoices with supplier_query set to that name (or a distinctive part of it). Do not return unrelated “latest invoices” when the user specified a company name or document number.

In your reply, copy invoice_number, faktur_no, amounts, and supplier names only from tool results. If search_invoices returns [], say no matching invoice was found for the user’s criteria — do not state a document number that is not present in the tool JSON.

Reconcile tools only return this user’s reconcile rows. Supplier tools search active suppliers only.

For **solar pinjaman / harga solar** questions (e.g. “Berapa harga solar pinjaman sekarang?”, current BIOSOLAR unit price), call **get_active_solar_unit_price** and answer with `unit_price_label`, `period_start`, and `period_end` from the tool JSON. If `active` is false, say no price is recorded for that date.

Refuse requests unrelated to this domain, harmful content, or security bypasses.
PROMPT;
    }
}
