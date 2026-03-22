<?php

namespace Tests\Feature\Auth;

use App\Models\Auth\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_users_can_view_the_password_edit_screen(): void
    {
        $user = $this->createUserWithPermissions(['password.write']);

        $this->actingAs($user)
            ->get('/admin/account/security-password/edit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Password/Edit'));
    }

    public function test_users_without_password_write_permission_cannot_view_the_password_edit_screen(): void
    {
        $user = $this->createUserWithPermissions(['dashboard.read']);

        $this->actingAs($user)
            ->get('/admin/account/security-password/edit')
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login_when_visiting_the_password_edit_screen(): void
    {
        $this->get('/admin/account/security-password/edit')
            ->assertRedirect('/login');
    }

    public function test_unverified_users_are_redirected_to_the_verification_notice_when_visiting_the_password_edit_screen(): void
    {
        $user = $this->createUserWithPermissions(['password.write'], [
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->get('/admin/account/security-password/edit')
            ->assertRedirect('/email/verify');
    }

    public function test_verified_users_can_update_their_password_and_stay_authenticated(): void
    {
        $user = $this->createUserWithPermissions(['password.write'], [
            'password' => 'old-password',
        ]);

        $this->actingAs($user)
            ->put('/admin/account/security-password', [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect('/admin/account/security-password/edit')
            ->assertSessionHas('success', '密码已更新。');

        $user = $user->fresh();

        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_users_without_password_write_permission_cannot_update_their_password(): void
    {
        $user = $this->createUserWithPermissions(['dashboard.read'], [
            'password' => 'old-password',
        ]);

        $this->actingAs($user)
            ->put('/admin/account/security-password', [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertForbidden();

        $user = $user->fresh();

        $this->assertTrue(Hash::check('old-password', $user->password));
        $this->assertFalse(Hash::check('new-password', $user->password));
    }

    public function test_password_is_not_updated_when_current_password_is_incorrect(): void
    {
        $user = $this->createUserWithPermissions(['password.write'], [
            'password' => 'old-password',
        ]);

        $this->actingAs($user)
            ->from('/admin/account/security-password/edit')
            ->put('/admin/account/security-password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect('/admin/account/security-password/edit')
            ->assertSessionHasErrors(['current_password']);

        $user = $user->fresh();

        $this->assertTrue(Hash::check('old-password', $user->password));
        $this->assertFalse(Hash::check('new-password', $user->password));
    }

    public function test_password_is_not_updated_when_confirmation_does_not_match(): void
    {
        $user = $this->createUserWithPermissions(['password.write'], [
            'password' => 'old-password',
        ]);

        $this->actingAs($user)
            ->from('/admin/account/security-password/edit')
            ->put('/admin/account/security-password', [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'mismatched-password',
            ])
            ->assertRedirect('/admin/account/security-password/edit')
            ->assertSessionHasErrors(['password']);

        $user = $user->fresh();

        $this->assertTrue(Hash::check('old-password', $user->password));
        $this->assertFalse(Hash::check('new-password', $user->password));
    }

    public function test_password_is_not_updated_when_the_new_password_does_not_match_default_rules(): void
    {
        $user = $this->createUserWithPermissions(['password.write'], [
            'password' => 'old-password',
        ]);

        $this->actingAs($user)
            ->from('/admin/account/security-password/edit')
            ->put('/admin/account/security-password', [
                'current_password' => 'old-password',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertRedirect('/admin/account/security-password/edit')
            ->assertSessionHasErrors(['password']);

        $user = $user->fresh();

        $this->assertTrue(Hash::check('old-password', $user->password));
        $this->assertFalse(Hash::check('short', $user->password));
    }
}
