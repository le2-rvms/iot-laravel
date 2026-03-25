<?php

namespace Tests\Feature\Console;

use App\Models\Admin\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCreateSuperUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_super_user_from_command_options(): void
    {
        $this->artisan('admin:create-super-user', [
            '--name' => 'Console Admin',
            '--email' => 'console-admin@example.com',
            '--password' => 'password',
        ])
            ->expectsOutputToContain('超级用户已创建。')
            ->expectsOutputToContain('邮箱: console-admin@example.com')
            ->expectsOutputToContain('角色: Super Admin')
            ->expectsOutputToContain('邮箱验证: 已完成')
            ->assertSuccessful();

        $user = AdminUser::query()->where('email', 'console-admin@example.com')->firstOrFail();

        $this->assertSame('Console Admin', $user->name);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('Super Admin'));

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_it_updates_an_existing_user_and_promotes_them_to_super_admin(): void
    {
        $user = AdminUser::factory()->create([
            'name' => 'Existing Admin',
            'email' => 'existing@example.com',
            'password' => 'old-password',
            'email_verified_at' => null,
        ]);

        $this->artisan('admin:create-super-user', [
            '--name' => 'Updated Admin',
            '--email' => 'existing@example.com',
            '--password' => 'new-password',
        ])
            ->expectsOutputToContain('超级用户已更新。')
            ->expectsOutputToContain('邮箱: existing@example.com')
            ->assertSuccessful();

        $user = $user->fresh();

        $this->assertSame('Updated Admin', $user->name);
        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('Super Admin'));

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_it_creates_a_super_user_from_default_options(): void
    {
        $this->artisan('admin:create-super-user')
            ->expectsOutputToContain('超级用户已创建。')
            ->expectsOutputToContain('邮箱: admin@example.com')
            ->expectsOutputToContain('角色: Super Admin')
            ->expectsOutputToContain('邮箱验证: 已完成')
            ->assertSuccessful();

        $user = AdminUser::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame('Admin', $user->name);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('Super Admin'));
    }

    public function test_it_fails_when_email_is_invalid(): void
    {
        $this->artisan('admin:create-super-user', [
            '--name' => 'Console Admin',
            '--email' => 'not-an-email',
            '--password' => 'password',
        ])
            ->expectsOutputToContain('The --email option must be a valid email address.')
            ->assertFailed();
    }
}
