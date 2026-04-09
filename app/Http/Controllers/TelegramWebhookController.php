<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTelegramDomainAssistantMessage;
use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TelegramWebhookController extends Controller
{
    public function webhook(Request $request, string $secret, TelegramBotService $telegram): Response
    {
        $expected = config('services.telegram.webhook_secret');
        if (! is_string($expected) || $expected === '' || ! hash_equals($expected, $secret)) {
            throw new NotFoundHttpException;
        }

        if (! config('services.telegram.assistant_enabled', false)) {
            return response('OK', 200);
        }

        $payload = $request->all();
        $message = $payload['message'] ?? null;
        if (! is_array($message)) {
            return response('OK', 200);
        }

        $chat = $message['chat'] ?? null;
        $from = $message['from'] ?? null;
        if (! is_array($chat) || ! is_array($from)) {
            return response('OK', 200);
        }

        if (($chat['type'] ?? '') !== 'private') {
            return response('OK', 200);
        }

        $text = $message['text'] ?? null;
        if (! is_string($text)) {
            return response('OK', 200);
        }

        $telegramUserId = isset($from['id']) ? (int) $from['id'] : 0;
        $chatId = isset($chat['id']) ? (int) $chat['id'] : 0;
        if ($telegramUserId <= 0 || $chatId === 0) {
            return response('OK', 200);
        }

        $user = User::query()->where('telegram_user_id', $telegramUserId)->first();
        if (! $user) {
            if ($telegram->isConfigured()) {
                $telegram->sendMessage(
                    $chatId,
                    'Your Telegram account is not linked to DDS. Ask an administrator to open Admin → Users → Edit your user and set the Telegram field to your @username or numeric ID. You may need to press Start in this chat first so Telegram accepts lookups.'
                );
            }

            return response('OK', 200);
        }

        if (config('services.telegram.dispatch_sync', true)) {
            ProcessTelegramDomainAssistantMessage::dispatchSync(
                $user->id,
                $chatId,
                $telegramUserId,
                $text
            );
        } else {
            ProcessTelegramDomainAssistantMessage::dispatch(
                $user->id,
                $chatId,
                $telegramUserId,
                $text
            );
        }

        return response('OK', 200);
    }
}
