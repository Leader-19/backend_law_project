<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\ReceiptPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_submission_is_pending_and_approval_is_idempotent(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $plan = SubscriptionPlan::create(['name' => 'Pro', 'slug' => 'pro', 'price' => 12.50, 'currency' => 'USD', 'duration_days' => 30, 'is_active' => true]);
        $service = app(ReceiptPaymentService::class);

        $payment = $service->submit($user, $plan, UploadedFile::fake()->image('receipt.png'), 'TX-100');

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'pending', 'amount_cents' => 1250]);
        Storage::disk('public')->assertExists($payment->receipt_path);

        $service->approve($payment);
        $service->approve($payment->fresh());

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'approved']);
        $this->assertDatabaseCount('user_subscriptions', 1);
        $this->assertDatabaseHas('user_subscriptions', ['user_id' => $user->id, 'subscription_plan_id' => $plan->id, 'status' => 'active']);
    }
}
