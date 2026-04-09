<?php

namespace Tests\Feature;

use App\Jobs\ProcessTelegramDomainAssistantMessage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TelegramDomainAssistantWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Config::set('services.telegram.assistant_enabled', true);
        Config::set('services.telegram.webhook_secret', 'test-webhook-secret');
        Config::set('services.telegram.bot_token', '123456:TEST');
    }

    public function test_webhook_rejects_wrong_secret(): void
    {
        $this->postJson(route('telegram.webhook', ['secret' => 'wrong']), [])
            ->assertNotFound();
    }

    public function test_webhook_dispatches_job_when_user_linked(): void
    {
        Queue::fake();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $user = User::factory()->create([
            'is_active' => true,
            'username' => 'tguser',
            'telegram_user_id' => 777001,
            'telegram_username' => 'testuser',
        ]);
        $user->assignRole('admin');

        $this->postJson(route('telegram.webhook', ['secret' => 'test-webhook-secret']), [
            'update_id' => 1,
            'message' => [
                'message_id' => 10,
                'text' => 'Hello assistant',
                'chat' => ['id' => 999888, 'type' => 'private'],
                'from' => ['id' => 777001, 'is_bot' => false, 'first_name' => 'Test'],
            ],
        ])->assertOk();

        Queue::assertPushed(ProcessTelegramDomainAssistantMessage::class, function (ProcessTelegramDomainAssistantMessage $job) use ($user) {
            return $job->userId === $user->id
                && $job->telegramChatId === 999888
                && $job->telegramUserId === 777001
                && $job->messageText === 'Hello assistant';
        });
    }

    public function test_webhook_ignores_non_private_chats(): void
    {
        Queue::fake();

        $this->postJson(route('telegram.webhook', ['secret' => 'test-webhook-secret']), [
            'message' => [
                'text' => 'Hi',
                'chat' => ['id' => 1, 'type' => 'group'],
                'from' => ['id' => 2],
            ],
        ])->assertOk();

        Queue::assertNothingPushed();
    }
}
