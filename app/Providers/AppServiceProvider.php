<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\Leader;
use App\Models\Registration;
use App\Models\Round;
use App\Models\User;
use App\Policies\LeaderPolicy;
use App\Policies\RegistrationPolicy;
use App\Policies\RoundPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Password::defaults(fn () => Password::min(8)->letters()->numbers());
        \Illuminate\Pagination\Paginator::defaultView('partials.pagination');

        $this->registerAuthorization();
        $this->registerRateLimiters();
    }

    private function registerAuthorization(): void
    {
        // Super admins can do everything — but only while their account is active.
        Gate::before(function (User $user) {
            if (! $user->is_active) {
                return false;
            }

            return $user->isSuperAdmin() ? true : null;
        });

        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, fn (User $user) => $user->hasPermission($permission));
        }

        // Abilities reserved for super admins (Gate::before grants them; everyone else is denied).
        foreach (['super-admin', 'manage-admins', 'manage-settings', 'manage-competitions', 'view-audit-logs'] as $ability) {
            Gate::define($ability, fn (User $user) => false);
        }

        Gate::define('access-admin', fn (User $user) => $user->isStaff() && $user->is_active);

        Gate::policy(Registration::class, RegistrationPolicy::class);
        Gate::policy(Round::class, RoundPolicy::class);
        Gate::policy(Leader::class, LeaderPolicy::class);
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('registrations', function (Request $request) {
            return [
                Limit::perMinute(6)->by('reg-ip:'.$request->ip()),
                Limit::perHour(40)->by('reg-ip-h:'.$request->ip()),
                Limit::perMinute(3)->by('reg-phone:'.preg_replace('/\D/', '', (string) $request->input('phone'))),
            ];
        });
        RateLimiter::for('code-lookup', fn (Request $r) => Limit::perMinute(30)->by('code:'.$r->ip()));
        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinute(5)->by('login:'.strtolower((string) $r->input('email')).'|'.$r->ip()),
            Limit::perMinute(20)->by('login-ip:'.$r->ip()),
        ]);
        RateLimiter::for('leader-signup', fn (Request $r) => Limit::perHour(5)->by('lsignup:'.$r->ip()));
        RateLimiter::for('live', fn (Request $r) => Limit::perMinute(90)->by('live:'.($r->user()?->id ?: $r->ip())));
        RateLimiter::for('free-courses', fn (Request $r) => Limit::perMinute(8)->by('fc:'.$r->ip()));
    }
}
