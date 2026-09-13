<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Category;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\DocumentLimitService;
use App\Services\SubscriptionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_is_given_the_free_plan_and_is_blocked_at_its_document_limit(): void
    {
        $user = User::factory()->create();
        $service = app(SubscriptionService::class);
        $subscription = $service->ensureFreeSubscription($user);

        $this->assertSame('free', $subscription->plan->slug);
        $this->assertSame(5, $subscription->plan->max_documents);

        $this->createDocuments($user, 5);
        $usage = app(DocumentLimitService::class)->usage($user);
        $this->assertTrue($usage['at_limit']);
        $this->expectException(AuthorizationException::class);
        app(DocumentLimitService::class)->ensureCanCreate($user);
    }

    public function test_expired_paid_subscription_falls_back_to_the_free_plan_without_deleting_documents(): void
    {
        $user = User::factory()->create();
        $pro = SubscriptionPlan::create(['name' => 'Pro', 'slug' => 'pro-test', 'price' => 9.99, 'max_documents' => 100, 'is_active' => true]);
        $user->subscriptions()->create(['subscription_plan_id' => $pro->id, 'status' => 'active', 'starts_at' => now()->subMonth(), 'ends_at' => now()->subMinute()]);
        $this->createDocuments($user, 6);

        app(SubscriptionService::class)->expireEndedSubscriptions();
        $current = app(SubscriptionService::class)->currentSubscriptionFor($user);

        $this->assertSame('free', $current->plan->slug);
        $this->assertSame(6, $user->documents()->count());
        $this->assertTrue(app(DocumentLimitService::class)->usage($user)['at_limit']);
    }

    private function createDocuments(User $user, int $count): void
    {
        $category = Category::create(['title' => 'Test category', 'user_id' => $user->id]);
        foreach (range(1, $count) as $number) {
            Document::create(['user_id' => $user->id, 'category_id' => $category->id, 'doc_name' => "test-{$number}.pdf", 'doc_title' => "Test {$number}", 'doc_upload' => "tests/{$number}.pdf"]);
        }
    }
}
