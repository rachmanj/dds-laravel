<?php

namespace App\Services;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssistantConversationManager
{
    public const SESSION_CONVERSATION_ID = 'domain_assistant.conversation_id';

    public function resolveConversation(Request $request, User $user, ?int $explicitConversationId = null): AssistantConversation
    {
        if ($explicitConversationId !== null) {
            $conversation = AssistantConversation::query()
                ->where('user_id', $user->id)
                ->where('id', $explicitConversationId)
                ->firstOrFail();
            $request->session()->put(self::SESSION_CONVERSATION_ID, $conversation->id);

            return $conversation;
        }

        $id = $request->session()->get(self::SESSION_CONVERSATION_ID);
        if ($id) {
            $conversation = AssistantConversation::query()
                ->where('user_id', $user->id)
                ->where('id', $id)
                ->first();
            if ($conversation) {
                return $conversation;
            }
        }

        $conversation = AssistantConversation::query()->create([
            'user_id' => $user->id,
        ]);
        $request->session()->put(self::SESSION_CONVERSATION_ID, $conversation->id);

        return $conversation;
    }

    public function createConversation(Request $request, User $user): AssistantConversation
    {
        $conversation = AssistantConversation::query()->create([
            'user_id' => $user->id,
        ]);
        $request->session()->put(self::SESSION_CONVERSATION_ID, $conversation->id);

        return $conversation;
    }

    public function getOrCreateTelegramConversation(User $user, int $telegramChatId): AssistantConversation
    {
        return AssistantConversation::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'telegram_chat_id' => $telegramChatId,
            ],
            [
                'title' => null,
            ]
        );
    }

    public function selectConversation(Request $request, User $user, AssistantConversation $conversation): void
    {
        abort_unless($conversation->user_id === $user->id, 404);
        $request->session()->put(self::SESSION_CONVERSATION_ID, $conversation->id);
    }

    public function deleteConversation(Request $request, User $user, AssistantConversation $conversation): void
    {
        abort_unless($conversation->user_id === $user->id, 404);
        if ((int) $request->session()->get(self::SESSION_CONVERSATION_ID) === (int) $conversation->id) {
            $request->session()->forget(self::SESSION_CONVERSATION_ID);
        }
        $conversation->delete();
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    public function openAiHistory(AssistantConversation $conversation, int $maxMessages = 20): array
    {
        $rows = AssistantMessage::query()
            ->where('assistant_conversation_id', $conversation->id)
            ->whereIn('role', ['user', 'assistant'])
            ->orderByDesc('id')
            ->limit($maxMessages)
            ->get()
            ->sortBy('id')
            ->values();

        return $rows->map(fn (AssistantMessage $m) => [
            'role' => $m->role,
            'content' => $m->content,
        ])->all();
    }

    public function appendExchange(AssistantConversation $conversation, string $userContent, string $assistantContent): void
    {
        $setTitle = ! $conversation->messages()->where('role', 'user')->exists();

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $userContent,
        ]);
        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $assistantContent,
        ]);
        $conversation->touch();

        if ($setTitle && ($conversation->title === null || $conversation->title === '')) {
            $conversation->title = Str::limit(trim($userContent), 80);
            $conversation->save();
        }
    }

    public function clear(Request $request, User $user): void
    {
        $id = $request->session()->get(self::SESSION_CONVERSATION_ID);
        if ($id) {
            AssistantConversation::query()
                ->where('user_id', $user->id)
                ->where('id', $id)
                ->delete();
        }
        $request->session()->forget(self::SESSION_CONVERSATION_ID);
        $request->session()->forget(DomainAssistantService::sessionKey());
    }

    public function todaysUserMessageCount(User $user): int
    {
        return AssistantMessage::query()
            ->where('role', 'user')
            ->where('created_at', '>=', now()->startOfDay())
            ->whereHas('conversation', fn ($q) => $q->where('user_id', $user->id))
            ->count();
    }
}
