<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['user_id', 'user_subscription_id', 'subscription_plan_id', 'provider', 'provider_payment_id', 'provider_invoice_id', 'amount_cents', 'currency', 'status', 'receipt_path', 'receipt_reference', 'paid_at', 'reviewed_at', 'reviewed_by', 'review_note', 'payload'];

    protected $casts = ['paid_at' => 'datetime', 'reviewed_at' => 'datetime', 'payload' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
