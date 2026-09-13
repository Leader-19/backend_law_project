<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;

class SubscriptionPlanController extends Controller
{
    public function index()
    {

    // Enum number
        $symbolMap = ['USD' => '$', 'KHR' => '៛', 'THB' => '฿'];

        // plan query from DB
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price')
            ->get(['id', 'name', 'slug', 'description', 'price', 'monthly_price_cents', 'yearly_price_cents', 'currency', 'duration_days', 'features', 'max_categories', 'max_documents', 'max_text_contents', 'max_storage_mb'])
            ->map(function ($plan) use ($symbolMap) {
                $symbol = $symbolMap[$plan->currency] ?? $plan->currency;
                $formattedPrice = $plan->currency === 'KHR'
                    ? number_format($plan->price).' '.$symbol
                    : $symbol.number_format($plan->price, 2);

                $yearlyCents = $plan->yearly_price_cents ?? ((int) round(((float) $plan->price) * 1200));
                $yearlyPrice = $yearlyCents / 100;
                $formattedYearlyPrice = $plan->currency === 'KHR' ? number_format($yearlyPrice).' '.$symbol : $symbol.number_format($yearlyPrice, 2);
                return array_merge($plan->toArray(), [
                    'currency_symbol' => $symbol,
                    'formatted_price' => $formattedPrice,
                    'formatted_yearly_price' => $formattedYearlyPrice,
                ]);
            });

            // Return result after query success
        return response()->json([
            'status' => 'success',
            'plans' => $plans,
        ]);
    }

    // Show details functions

    public function show(string $id)
    {
        // Enum variable 
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
