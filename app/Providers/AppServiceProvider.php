<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Paper;
use App\Models\Review;
use App\Models\User;
use App\Policies\PaperPolicy;
use App\Policies\ReviewPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Model policies
        Gate::policy(Paper::class, PaperPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);

        // Role gates (route-level / UI-level)
        Gate::define('view-oversight', fn (User $u) => $u->hasRole(UserRole::Executive, UserRole::Editor));
        Gate::define('manage-sectors', fn (User $u) => $u->hasRole(UserRole::Editor));
        Gate::define('manage-users', fn (User $u) => $u->hasRole(UserRole::Executive));
        Gate::define('manage-api-tokens', fn (User $u) => $u->hasRole(UserRole::Executive, UserRole::Editor, UserRole::Subscriber));

        // Open-access API throttle
        RateLimiter::for('catalog-api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }
}
