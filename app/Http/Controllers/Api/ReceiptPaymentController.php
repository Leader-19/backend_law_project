<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReceiptPaymentRequest;
use App\Models\SubscriptionPlan;
use App\Services\ReceiptPaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptPaymentController extends Controller
{
    public function store(StoreReceiptPaymentRequest $request, ReceiptPaymentService $payments): JsonResponse
    {
        $data = $request->validated();
        $payment = $payments->submit($request->user(), SubscriptionPlan::findOrFail($data['plan_id']), $request->file('receipt'), $data['billing_cycle'], $data['reference'] ?? null);

        return response()->json(['status' => 'pending', 'message' => 'Receipt submitted for review.', 'payment' => $payment->load('plan:id,name')], 202);
    }

    public function history(Request $request): JsonResponse
    {
        return response()->json(['payments' => $request->user()->payments()->with('plan:id,name')->latest()->paginate(15)]);
    }

    public function current(Request $request): JsonResponse
    {
        return response()->json(['subscription' => app(SubscriptionService::class)->currentSubscriptionFor($request->user())]);
    }
}
