<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\ReceiptPaymentService;
use App\Services\TelegramPaymentNotifier;
use Illuminate\Http\Request;

class TelegramPaymentWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramPaymentNotifier $telegram, ReceiptPaymentService $payments)
    {
        $secret = (string) config('services.telegram.webhook_secret');
        abort_if($secret === '', 503, 'Telegram webhook secret is not configured.');
        abort_unless(hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 403);
        $callback = $request->input('callback_query');
        if (! $callback || ! preg_match('/^payment:(\d+):(approve|reject):([a-f0-9]{32})$/', (string) ($callback['data'] ?? ''), $parts)) return response()->noContent();
        [, $id, $action, $signature] = $parts;
        if (! $telegram->validCallback((int) $id, $action, $signature)) return response()->noContent(403);
        $payment = Payment::find($id);
        $message = 'Payment was not found.';
        if ($payment?->status === 'pending') {
            $action === 'approve' ? $payments->approve($payment) : $payments->reject($payment, null, 'Rejected from Telegram');
            $message = $action === 'approve' ? 'Payment approved and subscription activated.' : 'Payment rejected.';
        } elseif ($payment) $message = 'This payment has already been reviewed.';
        $telegram->answerCallback((string) $callback['id'], $message);
        return response()->noContent();
    }
}
