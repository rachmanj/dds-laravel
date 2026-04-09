<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramSetWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook
                            {--url= : HTTPS base URL of the app (overrides APP_URL; required for local dev via ngrok/cloudflared)}';

    protected $description = 'Register Telegram bot webhook URL with Telegram (uses APP_URL and TELEGRAM_WEBHOOK_SECRET)';

    public function handle(): int
    {
        $token = config('services.telegram.bot_token');
        if (! is_string($token) || $token === '') {
            $this->error('TELEGRAM_BOT_TOKEN is not set.');

            return self::FAILURE;
        }

        $secret = config('services.telegram.webhook_secret');
        if (! is_string($secret) || $secret === '') {
            $this->error('TELEGRAM_WEBHOOK_SECRET is not set.');

            return self::FAILURE;
        }

        $optionUrl = $this->option('url');
        $base = is_string($optionUrl) && $optionUrl !== ''
            ? rtrim($optionUrl, '/')
            : rtrim((string) config('app.url'), '/');
        if ($base === '') {
            $this->error('APP_URL is not set. Pass --url=https://your-host for a one-off registration.');

            return self::FAILURE;
        }

        if (! str_starts_with($base, 'https://')) {
            $this->error('Telegram only accepts HTTPS webhooks. Base URL was: '.$base);
            $this->newLine();
            $this->line('Local development: expose Laravel with an HTTPS tunnel, then register using that URL:');
            $this->line('  <fg=gray>ngrok http 8000</>  (or cloudflared / similar)');
            $this->line('  <fg=gray>php artisan telegram:set-webhook --url="https://xxxx.ngrok-free.app"</>');
            $this->newLine();
            $this->line('Production: set APP_URL=https://your-real-domain in .env, then run this command again.');

            return self::FAILURE;
        }

        $webhookUrl = $base.'/telegram/webhook/'.$secret;

        $this->info('Registering webhook: '.$webhookUrl);

        $response = Http::timeout(30)->post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => $webhookUrl,
        ]);

        if (! $response->successful()) {
            $this->error('HTTP '.$response->status().': '.$response->body());

            return self::FAILURE;
        }

        $data = $response->json();
        if (! is_array($data)) {
            $this->error('Unexpected response: '.$response->body());

            return self::FAILURE;
        }

        if (! ($data['ok'] ?? false)) {
            $this->error('Telegram API error: '.json_encode($data));

            return self::FAILURE;
        }

        $this->info('Webhook registered successfully.');

        return self::SUCCESS;
    }
}
