<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetTelegramPaymentWebhook extends Command
{
    protected $signature = 'telegram:payments:set-webhook {url? : Public HTTPS URL for the Telegram payment webhook}';

    protected $description = 'Register the secure Telegram callback URL used by receipt approval buttons';

    public function handle(): int
    {
        $token = (string) config('services.telegram.bot_token');
        $secret = (string) config('services.telegram.webhook_secret');
        $url = $this->argument('url') ?: rtrim((string) config('app.url'), '/').'/api/webhooks/telegram/payments';

        if ($token === '' || $secret === '') {
            $this->error('Set TELEGRAM_BOT_TOKEN and TELEGRAM_WEBHOOK_SECRET before registering the webhook.');

            return self::FAILURE;
        }

        $response = Http::timeout(15)->post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => ['callback_query'],
        ]);

        if ($response->failed() || ! $response->json('ok')) {
            $this->error('Telegram rejected the webhook: '.$response->body());

            return self::FAILURE;
        }

        $this->info("Telegram payment webhook registered: {$url}");

        return self::SUCCESS;
    }
}
