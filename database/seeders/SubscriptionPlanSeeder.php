<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Basic access for new users.',
                'price' => 0,
                'currency' => 'USD',
                'duration_days' => null,
                'features' => ['Basic category access', 'Limited documents'],
                'max_categories' => 3,
                'max_documents' => 50,
                'max_text_contents' => 20,
                'max_storage_mb' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'Premium (USD)',
                'slug' => 'premium-usd',
                'description' => 'Enhanced access with more storage in USD.',
                'price' => 9.99,
                'currency' => 'USD',
                'duration_days' => 30,
                'features' => ['All category access', 'Unlimited documents', 'Priority support'],
                'max_categories' => null,
                'max_documents' => null,
                'max_text_contents' => null,
                'max_storage_mb' => 1000,
                'is_active' => true,
            ],
            [
                'name' => 'Premium (KHR)',
                'slug' => 'premium-khr',
                'description' => 'Enhanced access with more storage in Cambodian Riel.',
                'price' => 40000,
                'currency' => 'KHR',
                'duration_days' => 30,
                'features' => ['All category access', 'Unlimited documents', 'Priority support'],
                'max_categories' => null,
                'max_documents' => null,
                'max_text_contents' => null,
                'max_storage_mb' => 1000,
                'is_active' => true,
            ],
            [
                'name' => 'VIP (USD)',
                'slug' => 'vip-usd',
                'description' => 'Full unrestricted access in USD.',
                'price' => 29.99,
                'currency' => 'USD',
                'duration_days' => 30,
                'features' => ['All category access', 'Unlimited documents', 'Dedicated support', 'API access'],
                'max_categories' => null,
                'max_documents' => null,
                'max_text_contents' => null,
                'max_storage_mb' => 5000,
                'is_active' => true,
            ],
            [
                'name' => 'VIP (KHR)',
                'slug' => 'vip-khr',
                'description' => 'Full unrestricted access in Cambodian Riel.',
                'price' => 120000,
                'currency' => 'KHR',
                'duration_days' => 30,
                'features' => ['All category access', 'Unlimited documents', 'Dedicated support', 'API access'],
                'max_categories' => null,
                'max_documents' => null,
                'max_text_contents' => null,
                'max_storage_mb' => 5000,
                'is_active' => true,
            ],
            [
                'name' => 'VIP (THB)',
                'slug' => 'vip-thb',
                'description' => 'Full unrestricted access in Thai Baht.',
                'price' => 1050,
                'currency' => 'THB',
                'duration_days' => 30,
                'features' => ['All category access', 'Unlimited documents', 'Dedicated support', 'API access'],
                'max_categories' => null,
                'max_documents' => null,
                'max_text_contents' => null,
                'max_storage_mb' => 5000,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
