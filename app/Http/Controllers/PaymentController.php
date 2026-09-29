<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\UserSubscription;
use App\Services\ReceiptPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Payments/Index', [
            'payments' => Payment::query()->with(['user:id,name,email', 'plan:id,name,price,currency,duration_days', 'reviewer:id,name'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->when($request->filled('search'), fn ($q) => $q->where(fn ($query) => $query->where('receipt_reference', 'like', '%'.$request->string('search').'%')->orWhereHas('user', fn ($user) => $user->where('name', 'like', '%'.$request->string('search').'%')->orWhere('email', 'like', '%'.$request->string('search').'%'))))
                ->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only('status', 'search'),
        ]);
    }

    public function approve(Payment $payment, ReceiptPaymentService $payments): RedirectResponse
    {
        $payments->approve($payment, request()->user());

        return back()->with('success', 'Payment approved and subscription activated.');
    }

    public function reject(Payment $payment, Request $request, ReceiptPaymentService $payments): RedirectResponse
    {
        $payments->reject($payment, $request->user(), $request->string('review_note')->toString() ?: null);

        return back()->with('success', 'Payment rejected.');
    }

    /** Compatibility actions for the subscription-plan review table, whose rows are subscriptions rather than payments. */
    public function approveSubscription(UserSubscription $subscription, ReceiptPaymentService $payments): RedirectResponse
    {
        $payment = $subscription->payments()->where('status', 'pending')->latest()->firstOrFail();
        $payments->approve($payment, request()->user());

        return back()->with('success', 'Payment approved and subscription activated.');
    }

    public function rejectSubscription(UserSubscription $subscription, Request $request, ReceiptPaymentService $payments): RedirectResponse
    {
        $payment = $subscription->payments()->where('status', 'pending')->latest()->firstOrFail();
        $payments->reject($payment, $request->user(), $request->string('review_note')->toString() ?: null);

        return back()->with('success', 'Payment rejected.');
    }

    public function update(Payment $payment, Request $request): RedirectResponse
    {
        $payment->update($request->validate(['receipt_reference' => ['nullable', 'string', 'max:100'], 'review_note' => ['nullable', 'string', 'max:1000']]));

        return back()->with('success', 'Payment updated.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        abort_if($payment->status === 'approved', 422, 'Approved payments cannot be deleted.');
        if ($payment->receipt_path) {
            Storage::disk('public')->delete($payment->receipt_path);
        }
        $payment->subscription?->delete();
        $payment->delete();

        return back()->with('success', 'Payment deleted.');
    }
}
