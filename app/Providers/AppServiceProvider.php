<?php

namespace App\Providers;

use App\Support\PermissionRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('emqx-auth', function (Request $request) {
            // 结合来源 IP 和用户名限流，既限制爆破，又避免单一维度把正常设备全部互相牵连。
            return Limit::perMinute(60)->by(sprintf(
                '%s|%s',
                $request->ip(),
                (string) $request->input('username'),
            ));
        });

        Gate::before(function ($user, string $ability) {
            return $user->hasRole(PermissionRegistry::SUPER_ADMIN_ROLE) ? true : null;
        });
    }
}
