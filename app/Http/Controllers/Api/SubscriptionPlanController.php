<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        $symbolMap = ['USD' => '$', 'KHR' => '៛', 'THB' => '฿'];

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price')
            ->get(['id', 'name', 'slug', 'description', 'price', 'currency', 'duration_days', 'features', 'max_categories', 'max_documents', 'max_text_contents', 'max_storage_mb'])
            ->map(function ($plan) use ($symbolMap) {
                $symbol = $symbolMap[$plan->currency] ?? $plan->currency;
                $formattedPrice = $plan->currency === 'KHR'
                    ? number_format($plan->price).' '.$symbol
                    : $symbol.number_format($plan->price, 2);

                return array_merge($plan->toArray(), [
                    'currency_symbol' => $symbol,
                    'formatted_price' => $formattedPrice,
                ]);
            });

        return response()->json([
            'status' => 'success',
            'plans' => $plans,
        ]);
    }

    public function show(string $id)
    {
        $symbolMap = ['USD' => '$', 'KHR' => '៛', 'THB' => '฿'];
        $plan = SubscriptionPlan::where('is_active', true)->findOrFail($id);

        $symbol = $symbolMap[$plan->currency] ?? $plan->currency;
        $formattedPrice = $plan->currency === 'KHR'
            ? number_format($plan->price).' '.$symbol
            : $symbol.number_format($plan->price, 2);

        $data = array_merge($plan->toArray(), [
            'currency_symbol' => $symbol,
            'formatted_price' => $formattedPrice,
        ]);

        return response()->json([
            'status' => 'success',
            'plan' => $data,
        ]);
    }
}
