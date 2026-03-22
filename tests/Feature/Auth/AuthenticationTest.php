<?php

namespace Tests\Feature\Auth;

use App\Models\Auth\AdminUser;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->missing('devQuickLogins'));
    }

    public function test_dev_login_screen_exposes_dev_quick_logins(): void
    {
        $this->app['env'] = 'dev';

        $alpha = AdminUser::factory()->create([
            'name' => 'Alpha Admin',
            'email' => 'alpha@example.com',
        ]);
        $bravo = AdminUser::factory()->unverified()->create([
            'name' => 'Bravo Admin',
            'email' => 'bravo@example.com',
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->has('devQuickLogins', 2)
                ->where('devQuickLogins.0.id', $alpha->id)
                ->where('devQuickLogins.0.email', 'alpha@example.com')
                ->where('devQuickLogins.1.id', $bravo->id)
                ->where('devQuickLogins.1.email', 'bravo@example.com')
                ->where('devQuickLogins.1.email_verified_at', null));
    }

    public function test_users_can_authenticate_using_the_login_form(): void
    {
        $user = $this->createSuperAdmin([
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_can_login_with_remember_me_enabled(): void
    {
        $user = $this->createSuperAdmin([
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($this->app['auth']->guard()->viaRemember());
    }

    public function test_dev_environment_can_quick_login_as_an_existing_admin_user(): void
    {
        $this->app['env'] = 'dev';
        $user = $this->createSuperAdmin();
        $csrfToken = 'test-token';

        $this->withSession(['_token' => $csrfToken])->post("/login/dev-users/{$user->id}", [
            '_token' => $csrfToken,
        ])
            ->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_quick_login_does_not_verify_unverified_users(): void
    {
        $this->app['env'] = 'dev';
        $user = $this->createSuperAdmin([
            'email_verified_at' => null,
        ]);
        $csrfToken = 'test-token';

        $this->withSession(['_token' => $csrfToken])->post("/login/dev-users/{$user->id}", [
            '_token' => $csrfToken,
        ])
            ->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->refresh()->email_verified_at);

        $this->get('/admin/dashboard')
            ->assertRedirect('/email/verify');
    }

    public function test_non_dev_environment_cannot_use_dev_quick_login(): void
    {
        $user = AdminUser::factory()->create();

        $this->post("/login/dev-users/{$user->id}")
            ->assertNotFound();

        $this->assertGuest();
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = AdminUser::factory()->create([
            'password' => 'password',
        ]);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertSame(__('auth.failed'), session('errors')->getBag('default')->first('email'));
        $this->assertGuest();
    }

    public function test_login_validation_errors_are_returned_in_chinese(): void
    {
        $this->from('/login')->post('/login', [
            'email' => '',
            'password' => '',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors(['email', 'password']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('邮箱 不能为空。', $errors->first('email'));
        $this->assertSame('密码 不能为空。', $errors->first('password'));
    }

    public function test_forgot_password_screen_renders(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/ForgotPassword'));
    }

    public function test_users_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $user = AdminUser::factory()->create();

        $this->post('/forgot-password', [
            'email' => $user->email,
        ])->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_validation_errors_are_returned_in_chinese(): void
    {
        $this->from('/forgot-password')->post('/forgot-password', [
            'email' => '',
        ])->assertRedirect('/forgot-password')
            ->assertSessionHasErrors(['email']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('邮箱 不能为空。', $errors->first('email'));
    }

    public function test_reset_password_screen_renders(): void
    {
        $user = AdminUser::factory()->create();
        $token = Password::broker()->createToken($user);

        $this->get("/reset-password/{$token}?email={$user->email}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ResetPassword')
                ->where('email', $user->email)
                ->where('token', $token));
    }

    public function test_users_can_reset_their_password_with_a_valid_token(): void
    {
        $user = AdminUser::factory()->create();
        $token = Password::broker()->createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasNoErrors()
            ->assertRedirect('/login');
    }

    public function test_users_cannot_reset_their_password_with_an_invalid_token(): void
    {
        $user = AdminUser::factory()->create();

        $this->from('/reset-password/invalid')->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect('/reset-password/invalid')
            ->assertSessionHasErrors('email');

        $this->assertSame(__('passwords.token'), session('errors')->getBag('default')->first('email'));
    }

    public function test_reset_password_validation_errors_are_returned_in_chinese(): void
    {
        $this->from('/reset-password/invalid')->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect('/reset-password/invalid')
            ->assertSessionHasErrors(['email', 'password']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('邮箱 不能为空。', $errors->first('email'));
        $this->assertSame('密码 不能为空。', $errors->first('password'));
    }

    public function test_unverified_users_are_redirected_to_the_verification_notice(): void
    {
        $user = AdminUser::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertRedirect('/email/verify');
    }

    public function test_verification_notice_renders_for_unverified_users(): void
    {
        $user = AdminUser::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/email/verify')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/VerifyEmail'));
    }

    public function test_unverified_users_can_request_another_verification_email(): void
    {
        Notification::fake();

        $user = AdminUser::factory()->unverified()->create();

        $this->actingAs($user)
            ->post('/email/verification-notification')
            ->assertSessionHas('success', __('A new verification link has been sent to the email address you provided during registration.'));

        $this->actingAs($user)
            ->withSession([
                'success' => __('A new verification link has been sent to the email address you provided during registration.'),
            ])
            ->get('/email/verify')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/VerifyEmail')
                ->where('flash.success', __('A new verification link has been sent to the email address you provided during registration.')));

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
