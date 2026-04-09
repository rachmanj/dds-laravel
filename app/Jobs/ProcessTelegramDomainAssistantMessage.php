<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AssistantConversationManager;
use App\Services\AssistantRequestLogger;
use App\Services\DomainAssistantService;
use App\Services\TelegramBotService;
use App\Support\DomainAssistantListScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessTelegramDomainAssistantMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public function __construct(
        public int $userId,
        public int $telegramChatId,
        public int $telegramUserId,
        public string $messageText,
    ) {}

    public function handle(
        DomainAssistantService $service,
        AssistantConversationManager $conversations,
        TelegramBotService $telegram,
    ): void {
        if (! config('services.telegram.assistant_enabled', false)) {
            return;
        }

        if (! config('services.domain_assistant.enabled', false) || ! $service->isConfigured()) {
            if ($telegram->isConfigured()) {
                $telegram->sendMessage($this->telegramChatId, 'The Domain Assistant is not available right now. Please try again later or use the web app.');
            }

            return;
        }

        $user = User::query()->find($this->userId);
        if (! $user || ! $user->is_active) {
            return;
        }

        if ($user->telegram_user_id === null || (int) $user->telegram_user_id !== $this->telegramUserId) {
            return;
        }

        if (! $user->can('access-domain-assistant')) {
            $telegram->sendMessage($this->telegramChatId, 'You do not have permission to use the Domain Assistant. Ask an administrator to grant access.');

            return;
        }

        $dailyLimit = (int) config('services.domain_assistant.daily_user_message_limit', 0);
        if ($dailyLimit > 0 && $conversations->todaysUserMessageCount($user) >= $dailyLimit) {
            $telegram->sendMessage($this->telegramChatId, __('assistant.daily_limit'));

            return;
        }

        $text = trim($this->messageText);
        if ($text === '' || mb_strlen($text) > 12000) {
            $telegram->sendMessage($this->telegramChatId, 'Please send a shorter message (max 12000 characters).');

            return;
        }

        if (str_starts_with($text, '/')) {
            $cmd = strtolower(explode(' ', $text, 2)[0]);
            if (in_array($cmd, ['/start', '/help'], true)) {
                $telegram->sendMessage($this->telegramChatId, $this->helpText());

                return;
            }
        }

        $conversation = $conversations->getOrCreateTelegramConversation($user, $this->telegramChatId);
        $history = $conversations->openAiHistory($conversation);
        $started = hrtime(true);
        $showAllRecords = DomainAssistantListScope::forTelegram($user);

        try {
            $result = $service->appendUserMessageAndComplete(
                $user,
                $text,
                $history,
                $showAllRecords
            );
            $conversations->appendExchange($conversation, $text, $result['reply']);
            AssistantRequestLogger::log(
                $user,
                $conversation,
                $result['tools_invoked'],
                $showAllRecords,
                mb_strlen($text),
                $text,
                $started,
                true,
                null,
                null,
                null,
                $this->telegramChatId
            );
            $telegram->sendMessage($this->telegramChatId, $result['reply']);
        } catch (Throwable $e) {
            report($e);
            AssistantRequestLogger::log(
                $user,
                $conversation,
                [],
                $showAllRecords,
                mb_strlen($text),
                $text,
                $started,
                false,
                $e->getMessage(),
                null,
                null,
                $this->telegramChatId
            );
            $telegram->sendMessage($this->telegramChatId, 'Sorry, the assistant could not complete this request. Please try again later.');
        }
    }

    private function helpText(): string
    {
        return implode("\n", [
            'DDS Domain Assistant',
            '',
            'Ask questions about invoices, additional documents, distributions, suppliers, and workflows.',
            'Data scope matches the web assistant (same list visibility as “Show all records” off by default).',
            '',
            'Tip: Link this Telegram account in Admin → Users (Telegram field) if you have not already.',
        ]);
    }
}
