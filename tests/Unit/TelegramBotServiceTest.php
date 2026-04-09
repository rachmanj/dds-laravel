<?php

namespace Tests\Unit;

use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotServiceTest extends TestCase
{
    public function test_numeric_id_is_accepted_when_get_chat_fails(): void
    {
        Config::set('services.telegram.bot_token', '123:ABC');

        Http::fake([
            'api.telegram.org/*' => Http::response([
                'ok' => false,
                'description' => 'Bad Request: chat not found',
            ], 200),
        ]);

        $service = app(TelegramBotService::class);
        $resolved = $service->resolveChatIdentifier('987654321');

        $this->assertSame(987654321, $resolved['id']);
        $this->assertNull($resolved['username']);
    }

    public function test_get_chat_enriches_username_when_successful(): void
    {
        Config::set('services.telegram.bot_token', '123:ABC');

        Http::fake([
            'api.telegram.org/*' => Http::response([
                'ok' => true,
                'result' => [
                    'id' => 111,
                    'username' => 'jane',
                ],
            ], 200),
        ]);

        $service = app(TelegramBotService::class);
        $resolved = $service->resolveChatIdentifier('111');

        $this->assertSame(111, $resolved['id']);
        $this->assertSame('jane', $resolved['username']);
    }
}
