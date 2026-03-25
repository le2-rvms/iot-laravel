<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\Auth\EmailVerificationNotificationSentResponse;
use App\Models\Admin\AdminUser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\EmailVerificationNotificationSentResponse as EmailVerificationNotificationSentResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            EmailVerificationNotificationSentResponseContract::class,
            EmailVerificationNotificationSentResponse::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::loginView(function () {
            // 登录页始终拿到同一份对象结构，只有 enabled 会随环境变化。
            $devQuickLogin = [
                'enabled' => app()->environment('dev'),
                'users' => [],
            ];

            if ($devQuickLogin['enabled']) {
                // 开发快捷登录只暴露最小用户字段，并预先给出 POST 目标地址。
                $devQuickLogin['users'] = AdminUser::query()
                    ->orderBy('name')
                    ->orderBy('email')
                    ->get(['id', 'name', 'email', 'email_verified_at'])
                    ->map(fn (AdminUser $adminUser) => [
                        'id' => $adminUser->id,
                        'name' => $adminUser->name,
                        'email' => $adminUser->email,
                        'email_verified_at' => $adminUser->email_verified_at?->toDateTimeString(),
                        'login_url' => url("/login/dev-users/{$adminUser->id}"),
                    ])
                    ->all();
            }

            // 始终返回同一 prop 结构，避免登录页再按“字段是否存在”分支。
            return Inertia::render('Auth/Login', [
                'devQuickLogin' => $devQuickLogin,
            ]);
        });
        Fortify::requestPasswordResetLinkView(fn () => Inertia::render('Auth/ForgotPassword'));
        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('Auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));
        Fortify::verifyEmailView(fn () => Inertia::render('Auth/VerifyEmail'));

        RateLimiter::for('login', function (Request $request) {
            // 按 Fortify 的用户名字段和 IP 组合限流，保证基于邮箱的限流行为稳定。
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
