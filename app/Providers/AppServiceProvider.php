<?php

namespace App\Providers;

use App\Interfaces\Categories\CategoriesInterface;
use App\Interfaces\Documents\DocumentsInterface;
use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\UserPolicy;
use App\Repositories\Categories\CategoriesRepository;
use App\Repositories\Documents\DocumentsRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Repository and interface
        $this->app->bind(
            DocumentsInterface::class,
            DocumentsRepository::class,

        );

        $this->app->bind(
            CategoriesInterface::class,
            CategoriesRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        require_once app_path('Helpers/FilenameHelper.php');
        Gate::define('viewLogViewer', function ($user) {
            return in_array($user->email, [
                'admin@gmail.com', // replace with your actual admin email(s)
            ]);
        });

        // Implicitly grant 'Admin' role all permissions
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Admin') || $user->hasRole('admin') || $user->hasRole('Super Admin')) {
                return true;
            }
        });

        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        RateLimiter::for('backup-download', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Optional: general API limiter (if you use it)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }
}
