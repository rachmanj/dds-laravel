<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssistantChatRequest;
use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Models\AssistantRequestLog;
use App\Services\AssistantConversationManager;
use App\Services\DomainAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DomainAssistantController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! config('services.domain_assistant.enabled', false)) {
            return redirect()->route('welcome')
                ->with('error', 'The domain assistant is disabled.');
        }

        if (! $this->assistantReady()) {
            return redirect()->route('welcome')
                ->with('error', 'The domain assistant is not configured (OpenRouter API key missing).');
        }

        return view('assistant.index');
    }

    public function chat(
        AssistantChatRequest $request,
        DomainAssistantService $service,
        AssistantConversationManager $conversations
    ): JsonResponse|StreamedResponse {
        if (! config('services.domain_assistant.enabled', false)) {
            return response()->json(['error' => 'Assistant is disabled.'], 503);
        }

        if (! $service->isConfigured()) {
            return response()->json(['error' => 'Assistant is not configured.'], 503);
        }

        $user = $request->user();
        $dailyLimit = (int) config('services.domain_assistant.daily_user_message_limit', 0);
        if ($dailyLimit > 0 && $conversations->todaysUserMessageCount($user) >= $dailyLimit) {
            return response()->json(['error' => __('assistant.daily_limit')], 429);
        }

        $validated = $request->validated();
        $conversationId = isset($validated['conversation_id']) ? (int) $validated['conversation_id'] : null;
        $showAllRecords = $request->boolean('show_all_records')
            && $user->can('see-all-record-switch');

        if ($this->assistantWantsStreamResponse($request)) {
            return $this->streamAssistantChat(
                $request,
                $service,
                $conversations,
                $validated['message'],
                $showAllRecords,
                $conversationId
            );
        }

        $conversation = $conversations->resolveConversation($request, $user, $conversationId);
        $history = $conversations->openAiHistory($conversation);
        $started = hrtime(true);

        try {
            $result = $service->appendUserMessageAndComplete(
                $user,
                $validated['message'],
                $history,
                $showAllRecords
            );
            $conversations->appendExchange($conversation, $validated['message'], $result['reply']);
            $this->writeAssistantLog(
                $request,
                $conversation,
                $result['tools_invoked'],
                $showAllRecords,
                $started,
                true,
                null
            );

            return response()->json(['message' => $result['reply']]);
        } catch (Throwable $e) {
            report($e);
            $this->writeAssistantLog(
                $request,
                $conversation,
                [],
                $showAllRecords,
                $started,
                false,
                $e->getMessage()
            );

            return response()->json(['error' => 'The assistant could not complete this request. Please try again.'], 503);
        }
    }

    public function clear(Request $request, AssistantConversationManager $conversations): JsonResponse
    {
        $conversations->clear($request, $request->user());

        return response()->json(['ok' => true]);
    }

    public function conversationsIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        $activeId = $request->session()->get(AssistantConversationManager::SESSION_CONVERSATION_ID);
        $rows = AssistantConversation::query()
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get(['id', 'title', 'updated_at']);

        return response()->json([
            'active_conversation_id' => $activeId !== null ? (int) $activeId : null,
            'conversations' => $rows->map(fn (AssistantConversation $c) => [
                'id' => $c->id,
                'title' => $c->title ?: null,
                'updated_at' => $c->updated_at?->toIso8601String(),
            ])->values()->all(),
        ]);
    }

    public function conversationsStore(Request $request, AssistantConversationManager $manager): JsonResponse
    {
        $conversation = $manager->createConversation($request, $request->user());

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => null,
                'updated_at' => $conversation->updated_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function conversationMessages(Request $request, AssistantConversation $conversation): JsonResponse
    {
        $rows = AssistantMessage::query()
            ->where('assistant_conversation_id', $conversation->id)
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('id')
            ->get(['role', 'content', 'created_at']);

        return response()->json([
            'messages' => $rows->map(fn (AssistantMessage $m) => [
                'role' => $m->role,
                'content' => $m->content,
                'created_at' => $m->created_at?->toIso8601String(),
            ])->values()->all(),
        ]);
    }

    public function selectConversation(
        Request $request,
        AssistantConversationManager $manager,
        AssistantConversation $conversation
    ): JsonResponse {
        $manager->selectConversation($request, $request->user(), $conversation);

        return response()->json(['ok' => true]);
    }

    public function destroyConversation(
        Request $request,
        AssistantConversationManager $manager,
        AssistantConversation $conversation
    ): JsonResponse {
        $manager->deleteConversation($request, $request->user(), $conversation);

        return response()->json(['ok' => true]);
    }

    private function assistantWantsStreamResponse(Request $request): bool
    {
        if (! config('services.domain_assistant.streaming_enabled', true)) {
            return false;
        }
        if (config('services.domain_assistant.tools_enabled', true)) {
            return false;
        }

        return $request->boolean('stream');
    }

    /**
     * @param  list<string>  $toolsInvoked
     */
    private function writeAssistantLog(
        Request $request,
        AssistantConversation $conversation,
        array $toolsInvoked,
        bool $showAllRecords,
        int|float $startedHrTime,
        bool $success,
        ?string $errorSummary
    ): void {
        $durationMs = (int) round((hrtime(true) - $startedHrTime) / 1_000_000);

        try {
            AssistantRequestLog::query()->create([
                'user_id' => $request->user()->id,
                'assistant_conversation_id' => $conversation->id,
                'status' => $success ? AssistantRequestLog::STATUS_SUCCESS : AssistantRequestLog::STATUS_ERROR,
                'tools_invoked' => $toolsInvoked !== [] ? $toolsInvoked : null,
                'show_all_records' => $showAllRecords,
                'user_message_length' => mb_strlen((string) $request->input('message', '')),
                'duration_ms' => $durationMs,
                'error_summary' => $errorSummary !== null ? mb_substr($errorSummary, 0, 500) : null,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
            ]);
        } catch (Throwable $e) {
            report($e);
            Log::warning('assistant_request_log_failed', [
                'user_id' => $request->user()->id,
            ]);
        }
    }

    private function streamAssistantChat(
        Request $request,
        DomainAssistantService $service,
        AssistantConversationManager $conversations,
        string $userMessage,
        bool $showAllRecords,
        ?int $conversationId = null
    ): StreamedResponse {
        $user = $request->user();
        $conversation = $conversations->resolveConversation($request, $user, $conversationId);
        $history = $conversations->openAiHistory($conversation);
        $messages = array_merge($history, [['role' => 'user', 'content' => $userMessage]]);
        $started = hrtime(true);

        return response()->stream(function () use (
            $request,
            $service,
            $conversations,
            $conversation,
            $user,
            $messages,
            $showAllRecords,
            $userMessage,
            $started
        ): void {
            $full = '';
            try {
                foreach ($service->streamCompleteChatWithoutTools($user, $messages, $showAllRecords) as $delta) {
                    $full .= $delta;
                    echo 'data: '.json_encode(['c' => $delta], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)."\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
                echo 'data: '.json_encode(['done' => true])."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                $conversations->appendExchange($conversation, $userMessage, $full);
                $this->writeAssistantLog($request, $conversation, [], $showAllRecords, $started, true, null);
            } catch (Throwable $e) {
                report($e);
                echo 'data: '.json_encode(['error' => 'The assistant could not complete this request.'], JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                $this->writeAssistantLog($request, $conversation, [], $showAllRecords, $started, false, $e->getMessage());
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function assistantReady(): bool
    {
        return filled(config('services.openrouter.key'));
    }
}
