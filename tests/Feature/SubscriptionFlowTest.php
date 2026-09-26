<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $frontendUser;

    protected SubscriptionPlan $plan;

    protected Category $category1;

    protected Category $category2;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $adminRole = Role::create(['name' => 'Admin']);
        $permissions = [
            'dashboard.view', 'activity.view', 'activity.delete',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'category.view', 'category.create', 'category.edit', 'category.delete', 'category.manage',
            'document.view', 'document.create', 'document.edit', 'document.delete',
            'plans.view', 'plans.edit',
            'payments.view', 'payments.approve', 'payments.reject',
            'backup.view', 'backup.download', 'log.view',
        ];
        foreach ($permissions as $perm) {
            Permission::create(['name' => $perm, 'guard_name' => 'web']);
        }
        $adminRole->syncPermissions(Permission::all());

        // Create admin user with roles
        $this->admin = User::factory()->create(['registration_source' => 'admin']);
        $this->admin->assignRole('Admin');

        // Create frontend user
        $this->frontendUser = User::factory()->create(['registration_source' => 'frontend']);

        // Create subscription plan
        $this->plan = SubscriptionPlan::create([
            'name' => 'Basic Plan',
            'slug' => 'basic-plan',
            'price' => 9.99,
            'currency' => 'USD',
            'duration_days' => 30,
            'max_categories' => 5,
            'max_documents' => 50,
            'is_active' => true,
        ]);

        // Create categories (user_id is required)
        $this->category1 = Category::create(['title' => 'Legal Documents', 'description' => 'Legal docs', 'user_id' => $this->admin->id]);
        $this->category2 = Category::create(['title' => 'Contracts', 'description' => 'Contract templates', 'user_id' => $this->admin->id]);

        // Assign categories to the plan
        $this->plan->categories()->attach([$this->category1->id, $this->category2->id]);
    }

    /** @test */
    public function admin_can_assign_plan_to_frontend_user_via_web(): void
    {
        $response = $this->actingAs($this->admin)
            ->post("/frontend-users/{$this->frontendUser->id}/plans", [
                'subscription_plan_id' => $this->plan->id,
                'status' => 'active',
            ]);

        $response->assertRedirect();

        // Verify subscription was created
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $this->frontendUser->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        // Verify plan categories were auto-assigned
        $this->assertDatabaseHas('category_user', [
            'user_id' => $this->frontendUser->id,
            'category_id' => $this->category1->id,
            'permission' => 'view',
        ]);

        $this->assertDatabaseHas('category_user', [
            'user_id' => $this->frontendUser->id,
            'category_id' => $this->category2->id,
            'permission' => 'view',
        ]);
    }

    /** @test */
    public function admin_can_update_subscription_and_sync_plan_categories(): void
    {
        // First assign the plan
        $subscription = UserSubscription::create([
            'user_id' => $this->frontendUser->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        // Add a third category and assign to plan
        $category3 = Category::create(['title' => 'Court Filings', 'user_id' => $this->admin->id]);
        $this->plan->categories()->attach($category3->id);

        // Update the subscription
        $response = $this->actingAs($this->admin)
            ->post("/frontend-users/{$this->frontendUser->id}/plans", [
                'subscription_plan_id' => $this->plan->id,
                'subscription_id' => $subscription->id,
                'status' => 'active',
            ]);

        $response->assertRedirect();

        // Verify the third category was also auto-assigned
        $this->assertDatabaseHas('category_user', [
            'user_id' => $this->frontendUser->id,
            'category_id' => $category3->id,
            'permission' => 'view',
        ]);
    }

    /** @test */
    public function existing_category_permission_is_not_overwritten(): void
    {
        // Manually assign a category with 'edit' permission
        $this->category1->users()->attach($this->frontendUser->id, ['permission' => 'edit']);

        // Assign the plan (which includes category1)
        $response = $this->actingAs($this->admin)
            ->post("/frontend-users/{$this->frontendUser->id}/plans", [
                'subscription_plan_id' => $this->plan->id,
                'status' => 'active',
            ]);

        $response->assertRedirect();

        // Verify the original 'edit' permission was NOT overwritten
        $this->assertDatabaseHas('category_user', [
            'user_id' => $this->frontendUser->id,
            'category_id' => $this->category1->id,
            'permission' => 'edit',
        ]);
    }

    /** @test */
    public function user_can_see_only_assigned_categories_on_dashboard(): void
    {
        // Grant dashboard.view permission
        $this->frontendUser->givePermissionTo('dashboard.view');

        // Assign only category1 to the user
        $this->category1->users()->attach($this->frontendUser->id, ['permission' => 'view']);

        // Verify getViewableCategoryIds only returns the assigned category
        $viewableIds = $this->frontendUser->getViewableCategoryIds();

        $this->assertContains($this->category1->id, $viewableIds);
        $this->assertNotContains($this->category2->id, $viewableIds);
    }

    /** @test */
    public function plan_categories_returned_in_api_response(): void
    {
        $planCategories = $this->plan->categories;
        $this->assertCount(2, $planCategories);
        $this->assertTrue($planCategories->contains('id', $this->category1->id));
        $this->assertTrue($planCategories->contains('id', $this->category2->id));
    }

    /** @test */
    public function user_without_plan_cannot_create_categories(): void
    {
        // A frontend user without a subscription cannot create
        $this->assertFalse($this->frontendUser->canCreateCategory());
        $this->assertFalse($this->frontendUser->canCreateDocument());
    }

    /** @test */
    public function user_with_plan_can_create_up_to_limit(): void
    {
        // Create the plan subscription
        UserSubscription::create([
            'user_id' => $this->frontendUser->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $this->frontendUser->refresh();

        // User has 0 categories, limit is 5, so should be able to create
        $this->assertTrue($this->frontendUser->canCreateCategory());

        // Create categories up to the limit
        for ($i = 0; $i < 5; $i++) {
            Category::create(['title' => "Limit Test Cat {$i}", 'user_id' => $this->frontendUser->id]);
        }

        $this->frontendUser->refresh();

        // Now at limit (5), cannot create more
        $this->assertFalse($this->frontendUser->canCreateCategory());
    }

    /** @test */
    public function admin_has_no_subscription_limits(): void
    {
        $this->assertNull($this->admin->categoryLimit());
        $this->assertNull($this->admin->documentLimit());
        $this->assertTrue($this->admin->canCreateCategory());
        $this->assertTrue($this->admin->canCreateDocument());
    }

    /** @test */
    public function unauthorized_user_cannot_assign_plan(): void
    {
        $regularUser = User::factory()->create(['registration_source' => 'frontend']);

        $response = $this->actingAs($regularUser)
            ->post("/frontend-users/{$this->frontendUser->id}/plans", [
                'subscription_plan_id' => $this->plan->id,
                'status' => 'active',
            ]);

        // Should be forbidden (403) or redirect to unauthorized page
        $response->assertForbidden();
    }

    /** @test */
    public function viewable_category_ids_include_ancestors(): void
    {
        $parent = Category::create(['title' => 'Parent Category', 'user_id' => $this->admin->id]);
        $child = Category::create(['title' => 'Child Category', 'parent_id' => $parent->id, 'user_id' => $this->admin->id]);

        // Assign only the child to the user
        $child->users()->attach($this->frontendUser->id, ['permission' => 'view']);

        $viewableIds = $this->frontendUser->getViewableCategoryIds();

        // User should be able to see both child AND parent (ancestor)
        $this->assertContains($child->id, $viewableIds);
        $this->assertContains($parent->id, $viewableIds);
    }
}
