<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookEvent extends Model
{
    protected $fillable = ['provider', 'event_id', 'event_type', 'processed_at'];
    protected $casts = ['processed_at' => 'datetime'];
}
