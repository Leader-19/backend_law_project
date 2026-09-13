# Stripe subscription setup

1. Run `php artisan migrate` and `php artisan db:seed --class=SubscriptionPlanSeeder`.
   This creates the Free (5 documents), Pro (100), and Business (1,000) plans.
2. In Stripe, create a Product for Pro and Business. Create one recurring monthly Price and one recurring yearly Price for each product.
3. Put `STRIPE_KEY`, `STRIPE_SECRET`, and `STRIPE_WEBHOOK_SECRET` in the server environment. Keep the last two secret; Vue never receives them.
4. Store Stripe's IDs on each plan, for example with Tinker:

```php
$pro = App\Models\SubscriptionPlan::where('slug', 'pro')->first();
$pro->update([
  'stripe_product_id' => 'prod_...',
  'stripe_monthly_price_id' => 'price_...',
  'stripe_yearly_price_id' => 'price_...',
]);
```

5. Configure Stripe to send these events to `https://your-domain.com/api/webhooks/stripe`:
   `checkout.session.completed`, `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`, `invoice.paid`, and `invoice.payment_failed`.
6. Run Laravel's scheduler continuously in production: `php artisan schedule:work` (or configure `php artisan schedule:run` every minute via cron).

The success redirect is only a user experience step. `StripeWebhookController` verifies Stripe's signature, records the event ID to make retries safe, then activates the plan. Expiration marks the paid subscription expired; the next entitlement check automatically assigns Free and never deletes documents.
