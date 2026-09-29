<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramPaymentNotifier
{
    public function notify(Payment $payment): void
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');
        if (! $token || ! $chatId) {
            return;
        }
        $interval = $payment->subscription?->billing_interval ?? 'monthly';
        $text = "New receipt #{$payment->id}\nUser: {$payment->user->name}\nEmail: {$payment->user->email}\nPlan: {$payment->plan->name}\nBilling: {$interval}\nAmount: {$payment->currency} ".number_format($payment->amount_cents / 100, 2);
        $keyboard = ['inline_keyboard' => [[
            ['text' => 'Approve', 'callback_data' => $this->callback($payment, 'approve')],
            ['text' => 'Reject', 'callback_data' => $this->callback($payment, 'reject')],
        ]]];
        try {
            $filePath = Storage::disk('public')->path($payment->receipt_path);
            if (! is_file($filePath)) {
                Log::warning('Telegram payment notification skipped: receipt file is missing', ['payment_id' => $payment->id]);

                return;
            }
            $response = Http::timeout(10)->attach('photo', fopen($filePath, 'r'), basename($payment->receipt_path))
                ->post("https://api.telegram.org/bot{$token}/sendPhoto", ['chat_id' => $chatId, 'caption' => $text, 'reply_markup' => json_encode($keyboard)]);
            if ($response->failed()) {
                Log::warning('Telegram payment notification was rejected', ['payment_id' => $payment->id, 'status' => $response->status(), 'telegram_response' => $response->json()]);
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram payment notification failed', ['payment_id' => $payment->id, 'exception' => $e->getMessage()]);
        }
    }

    public function validCallback(int $id, string $action, string $signature): bool
    {
        return in_array($action, ['approve', 'reject'], true) && hash_equals($this->signature($id, $action), $signature);
    }

    public function answerCallback(string $callbackId, string $message): void
    {
        $token = config('services.telegram.bot_token');
        if (! $token) {
            return;
        }
        $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/answerCallbackQuery", ['callback_query_id' => $callbackId, 'text' => $message]);
        if ($response->failed()) {
            Log::warning('Telegram callback acknowledgement failed', ['status' => $response->status(), 'telegram_response' => $response->json()]);
        }
    }

    private function callback(Payment $payment, string $action): string
    {
        return 'payment:'.$payment->id.':'.$action.':'.$this->signature($payment->id, $action);
    }

    /** Telegram callback_data is limited to 64 bytes; 32 hex HMAC characters are sufficient here. */
    private function signature(int $id, string $action): string
    {
        return substr(hash_hmac('sha256', "$id|$action", config('app.key')), 0, 32);
    }
}
