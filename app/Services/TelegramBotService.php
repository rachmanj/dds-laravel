<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramBotService
{
    private const SEND_MESSAGE_URL = 'https://api.telegram.org/bot%s/sendMessage';

    private const GET_CHAT_URL = 'https://api.telegram.org/bot%s/getChat';

    private ?string $lastApiMessage = null;

    public function isConfigured(): bool
    {
        return filled($this->token());
    }

    public function token(): ?string
    {
        $t = config('services.telegram.bot_token');
        if (! is_string($t)) {
            return null;
        }
        $t = trim($t, " \t\n\r\0\x0B'\"");

        return $t !== '' ? $t : null;
    }

    public function lastApiMessage(): ?string
    {
        return $this->lastApiMessage;
    }

    /**
     * @return array{id: int, username: ?string}|null
     */
    public function resolveChatIdentifier(string $raw): ?array
    {
        $this->lastApiMessage = null;

        if (! $this->isConfigured()) {
            return null;
        }

        $s = $this->normalizeTelegramInput($raw);
        if ($s === '') {
            return null;
        }

        if (preg_match('/^\d+$/', $s)) {
            $id = (int) $s;
            if ($id <= 0) {
                return null;
            }

            $info = $this->getChat($s);
            if ($info !== null) {
                return ['id' => (int) $info['id'], 'username' => $info['username'] ?? null];
            }

            // getChat often fails for a user ID until they have messaged this bot at least once.
            // Webhook matching uses message.from.id; trusting the admin-entered numeric ID is safe.
            $this->lastApiMessage = null;

            return ['id' => $id, 'username' => null];
        }

        $chatParam = $this->toTelegramUsernameChatParam($s);
        if ($chatParam === null) {
            $this->lastApiMessage = 'Invalid username format. Use 5–32 characters (letters, numbers, underscore), or paste the numeric user ID.';

            return null;
        }

        $info = $this->getChat($chatParam);

        return $info !== null
            ? ['id' => (int) $info['id'], 'username' => $info['username'] ?? null]
            : null;
    }

    /**
     * Accepts pasted t.me / telegram.me links and bare usernames.
     */
    private function normalizeTelegramInput(string $raw): string
    {
        $s = trim($raw);
        if ($s === '') {
            return '';
        }

        if (preg_match('#^https?://(www\.)?(t\.me|telegram\.me)/(?P<u>[a-zA-Z0-9_]+)#i', $s, $m)) {
            return $m['u'];
        }

        return $s;
    }

    /**
     * Telegram usernames are matched case-insensitively; API expects @username.
     */
    private function toTelegramUsernameChatParam(string $s): ?string
    {
        $u = ltrim($s, '@');
        if ($u === '') {
            return null;
        }

        if (! preg_match('/^[a-zA-Z0-9_]{5,32}$/', $u)) {
            return null;
        }

        return '@'.strtolower($u);
    }

    /**
     * Telegram recommends POST with form body; GET + query string often breaks @username (HTTP 400).
     *
     * @return array{id: int, username?: string}|null
     */
    private function getChat(string $chatId): ?array
    {
        try {
            $url = sprintf(self::GET_CHAT_URL, $this->token());
            $response = Http::timeout(15)
                ->asForm()
                ->post($url, [
                    'chat_id' => $chatId,
                ]);

            $json = $response->json();
            if (is_array($json) && ! empty($json['ok']) && isset($json['result']['id'])) {
                $result = $json['result'];

                return [
                    'id' => (int) $result['id'],
                    'username' => isset($result['username']) ? (string) $result['username'] : null,
                ];
            }

            $description = is_array($json) ? ($json['description'] ?? null) : null;
            $desc = is_string($description) ? $description : null;
            if ($desc === null && is_array($json) && isset($json['error_code'])) {
                $desc = 'Telegram error_code '.(string) $json['error_code'];
            }
            if ($desc === null) {
                $desc = 'HTTP '.$response->status().(mb_strlen($response->body()) > 0 ? ': '.mb_substr($response->body(), 0, 200) : '');
            }
            $this->lastApiMessage = $desc;
            Log::info('telegram_get_chat_failed', [
                'chat_id' => $chatId,
                'http_status' => $response->status(),
                'description' => $desc,
            ]);

            return null;
        } catch (Throwable $e) {
            report($e);
            $this->lastApiMessage = $e->getMessage();

            return null;
        }
    }

    public function sendMessage(int|string $chatId, string $text): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $ok = true;
        foreach ($this->chunkText($text) as $chunk) {
            try {
                $url = sprintf(self::SEND_MESSAGE_URL, $this->token());
                $response = Http::timeout(60)->post($url, [
                    'chat_id' => $chatId,
                    'text' => $chunk,
                    'disable_web_page_preview' => true,
                ]);

                if (! $response->successful()) {
                    Log::warning('telegram_send_message_failed', [
                        'status' => $response->status(),
                        'body' => mb_substr($response->body(), 0, 500),
                    ]);
                    $ok = false;
                }
            } catch (Throwable $e) {
                report($e);
                $ok = false;
            }
        }

        return $ok;
    }

    /**
     * @return list<string>
     */
    private function chunkText(string $text): array
    {
        $max = 4000;
        if (mb_strlen($text) <= $max) {
            return [$text];
        }

        $chunks = [];
        $offset = 0;
        $len = mb_strlen($text);
        while ($offset < $len) {
            $chunks[] = mb_substr($text, $offset, $max);
            $offset += $max;
        }

        return $chunks;
    }
}
