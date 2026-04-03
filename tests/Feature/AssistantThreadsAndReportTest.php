<?php

namespace Tests\Feature;

use App\Models\AssistantConversation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AssistantThreadsAndReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Config::set('services.domain_assistant.enabled', true);
        Config::set('services.openrouter.key', 'test-key');
    }

    public function test_assistant_user_can_list_and_create_conversations(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'username' => 'assist1',
        ]);
        $user->assignRole('admin');

        $this->actingAs($user)->getJson(route('assistant.conversations.index'))
            ->assertOk()
            ->assertJsonStructure(['active_conversation_id', 'conversations']);

        $this->actingAs($user)->postJson(route('assistant.conversations.store'))
            ->assertCreated()
            ->assertJsonPath('conversation.id', AssistantConversation::query()->first()->id);
    }

    public function test_user_cannot_open_another_users_conversation_messages(): void
    {
        $owner = User::factory()->create([
            'is_active' => true,
            'username' => 'owner',
        ]);
        $owner->assignRole('admin');

        $other = User::factory()->create([
            'is_active' => true,
            'username' => 'other',
        ]);
        $other->assignRole('admin');

        $conversation = AssistantConversation::query()->create([
            'user_id' => $owner->id,
        ]);

        $this->actingAs($other)->getJson(route('assistant.conversations.messages', $conversation))
            ->assertNotFound();
    }

    public function test_admin_can_view_assistant_report(): void
    {
        $admin = User::factory()->create([
            'is_active' => true,
            'username' => 'repadmin',
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.assistant-report.index'))
            ->assertOk();
    }

    public function test_non_admin_cannot_view_assistant_report(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'username' => 'logrep',
        ]);
        $user->assignRole('logistic');

        $this->actingAs($user)->get(route('admin.assistant-report.index'))
            ->assertForbidden();
    }
}
