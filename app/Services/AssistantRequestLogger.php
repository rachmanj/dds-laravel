<?php

namespace App\Services;

use App\Models\AssistantConversation;
use App\Models\AssistantRequestLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class AssistantRequestLogger
{
    private const USER_MESSAGE_MAX_CHARS = 10000;

    /**
     * @param  list<string>  $toolsInvoked
     */
    public static function log(
        User $user,
        AssistantConversation $conversation,
        array $toolsInvoked,
        bool $showAllRecords,
        int $userMessageLength,
        string $userMessageText,
        int|float $startedHrTime,
        bool $success,
        ?string $errorSummary,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?int $telegramChatId = null,
    ): void {
        $durationMs = (int) round((hrtime(true) - $startedHrTime) / 1_000_000);
        $snapshot = self::truncateUserMessage($userMessageText);

        try {
            AssistantRequestLog::query()->create([
                'user_id' => $user->id,
                'assistant_conversation_id' => $conversation->id,
                'status' => $success ? AssistantRequestLog::STATUS_SUCCESS : AssistantRequestLog::STATUS_ERROR,
                'tools_invoked' => $toolsInvoked !== [] ? $toolsInvoked : null,
                'show_all_records' => $showAllRecords,
                'user_message_length' => $userMessageLength,
                'user_message' => $snapshot !== '' ? $snapshot : null,
                'duration_ms' => $durationMs,
                'error_summary' => $errorSummary !== null ? mb_substr($errorSummary, 0, 500) : null,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 2000) : null,
                'telegram_chat_id' => $telegramChatId,
            ]);
        } catch (Throwable $e) {
            report($e);
            Log::warning('assistant_request_log_failed', [
                'user_id' => $user->id,
            ]);
        }
    }

    private static function truncateUserMessage(string $text): string
    {
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) <= self::USER_MESSAGE_MAX_CHARS) {
            return $text;
        }

        return mb_substr($text, 0, self::USER_MESSAGE_MAX_CHARS);
    }
}
