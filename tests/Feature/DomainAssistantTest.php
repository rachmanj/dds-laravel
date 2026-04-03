<?php

namespace Tests\Feature;

use App\Models\AssistantRequestLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DomainAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Config::set('services.domain_assistant.tools_enabled', false);
    }

    public function test_guest_cannot_access_assistant(): void
    {
        $this->get(route('assistant.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'username' => 'loguser',
        ]);
        $user->assignRole('logistic');

        $this->actingAs($user)->get(route('assistant.index'))->assertForbidden();
    }

    public function test_user_with_permission_can_view_when_enabled_and_configured(): void
    {
        Config::set('services.domain_assistant.enabled', true);
        Config::set('services.openrouter.key', 'test-key');

        $user = User::factory()->create([
            'is_active' => true,
            'username' => 'adminuser',
        ]);
        $user->assignRole('admin');

        $this->actingAs($user)->get(route('assistant.index'))->assertOk();
    }

    public function test_chat_returns_assistant_message(): void
    {
        Config::set('services.domain_assistant.enabled', true);
        Config::set('services.domain_assistant.tools_enabled', false);
        Config::set('services.openrouter.key', 'test-key');
        Config::set('services.openrouter.chat_model', 'deepseek/deepseek-chat');

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Hello from the model.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'is_active' => true,
            'username' => 'adminuser2',
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->postJson(route('assistant.chat'), [
            'message' => 'What is an invoice in DDS?',
        ]);

        $response->assertOk()->assertJson([
            'message' => 'Hello from the model.',
        ]);

        $this->assertDatabaseHas('assistant_messages', [
            'role' => 'user',
        ]);
        $this->assertDatabaseHas('assistant_messages', [
            'role' => 'assistant',
        ]);
        $this->assertSame(1, AssistantRequestLog::query()->where('status', AssistantRequestLog::STATUS_SUCCESS)->count());
    }

    public function test_daily_message_limit_returns_429(): void
    {
        Config::set('services.domain_assistant.enabled', true);
        Config::set('services.domain_assistant.tools_enabled', false);
        Config::set('services.domain_assistant.daily_user_message_limit', 1);
        Config::set('services.openrouter.key', 'test-key');

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'First reply.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'is_active' => true,
            'username' => 'adminlimit',
        ]);
        $user->assignRole('admin');

        $this->actingAs($user)->postJson(route('assistant.chat'), [
            'message' => 'One',
        ])->assertOk();

        $this->actingAs($user)->postJson(route('assistant.chat'), [
            'message' => 'Two',
        ])->assertStatus(429);
    }
}
