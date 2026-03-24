<?php

namespace Tests\Feature\Users;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Auth\AdminRole;
use App\Models\Auth\AdminUser;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_users_can_view_the_users_index(): void
    {
        $admin = $this->createSuperAdmin();

        AdminUser::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get('/admin/admin-users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AdminUsers/Index')
                ->has('users.data', 4)
                ->where('filters', [])
                ->where('auth.access', fn ($access) => ($access['admin-user.read'] ?? false) === true)
                ->missing('auth.roles'));
    }

    public function test_unverified_users_cannot_access_the_users_index(): void
    {
        $admin = AdminUser::factory()->unverified()->create();

        $this->actingAs($admin)
            ->get('/admin/admin-users')
            ->assertRedirect('/email/verify');
    }

    public function test_verified_users_can_view_the_dashboard(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.usersCount', 1));
    }

    public function test_users_can_be_created_and_receive_a_verification_email(): void
    {
        Notification::fake();

        $admin = $this->createSuperAdmin();
        $role = AdminRole::create([
            'name' => 'Editor',
            'guard_name' => 'web',
        ]);
        $role->syncPermissions(['admin-user.read']);

        $this->actingAs($admin)
            ->post('/admin/admin-users', [
                'name' => 'New User',
                'email' => 'new-user@example.com',
                'password' => 'password',
                'roles' => ['Editor'],
            ])
            ->assertRedirect('/admin/admin-users');

        $user = AdminUser::where('email', 'new-user@example.com')->firstOrFail();

        $this->assertSame('New User', $user->name);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('Editor'));
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_users_can_be_updated_and_email_changes_reset_verification(): void
    {
        Notification::fake();

        $admin = $this->createSuperAdmin();
        $user = AdminUser::factory()->create([
            'email_verified_at' => now(),
        ]);
        $role = AdminRole::create([
            'name' => 'Manager',
            'guard_name' => 'web',
        ]);
        $role->syncPermissions(['admin-user.read', 'admin-user.write']);

        $this->actingAs($admin)
            ->put("/admin/admin-users/{$user->id}", [
                'name' => 'Updated User',
                'email' => 'updated@example.com',
                'password' => '',
                'roles' => ['Manager'],
            ])
            ->assertRedirect("/admin/admin-users/{$user->id}/edit");

        $user->refresh();

        $this->assertSame('Updated User', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('Manager'));
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_users_can_be_deleted(): void
    {
        $admin = $this->createSuperAdmin();
        $user = AdminUser::factory()->create();

        $this->actingAs($admin)
            ->delete("/admin/admin-users/{$user->id}")
            ->assertRedirect('/admin/admin-users');

        $this->assertDatabaseMissing((new AdminUser)->getTable(), [
            'id' => $user->id,
        ]);
    }

    public function test_users_can_be_filtered_by_search_callback(): void
    {
        $admin = $this->createSuperAdmin();

        AdminUser::factory()->create([
            'name' => 'Alice Cooper',
            'email' => 'alice@example.com',
        ]);

        AdminUser::factory()->create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
        ]);

        $this->actingAs($admin)
            ->get('/admin/admin-users?search__func=alice')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AdminUsers/Index')
                ->where('filters.search__func', 'alice')
                ->has('users.data', 1)
                ->where('users.data.0.email', 'alice@example.com'));
    }

    public function test_users_can_be_filtered_by_declared_field_operator(): void
    {
        $admin = $this->createSuperAdmin();

        AdminUser::factory()->create([
            'name' => 'Alice Cooper',
            'email' => 'alice@example.com',
        ]);
        AdminUser::factory()->create([
            'name' => 'Alice Smith',
            'email' => 'alice.smith@example.com',
        ]);

        $this->actingAs($admin)
            ->get('/admin/admin-users?name__eq=Alice Cooper')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AdminUsers/Index')
                ->where('filters.name__eq', 'Alice Cooper')
                ->has('users.data', 1)
                ->where('users.data.0.email', 'alice@example.com'));
    }

    public function test_users_index_supports_partial_reload_props(): void
    {
        $admin = $this->createSuperAdmin();
        AdminUser::factory()->create([
            'name' => 'Partial Match',
            'email' => 'partial@example.com',
        ]);

        $version = app(HandleInertiaRequests::class)->version(request()) ?? '';

        $this->actingAs($admin)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Inertia-Version' => $version,
                'X-Inertia-Partial-Component' => 'AdminUsers/Index',
                'X-Inertia-Partial-Data' => 'users,filters',
            ])
            ->get('/admin/admin-users?search__func=partial')
            ->assertOk()
            ->assertJsonPath('component', 'AdminUsers/Index')
            ->assertJsonPath('props.filters.search__func', 'partial')
            ->assertJsonPath('props.users.data.0.email', 'partial@example.com');
    }

    public function test_legacy_search_query_is_rejected_with_a_validation_error(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->get('/admin/admin-users?search=alice')
            ->assertStatus(422)
            ->assertJsonPath('errors.search.0', '筛选条件格式无效，必须使用 field__operator 形式。');
    }

    public function test_read_only_users_can_view_users_but_cannot_modify_them(): void
    {
        $user = $this->createUserWithPermissions(['admin-user.read']);
        $target = AdminUser::factory()->create();

        $this->actingAs($user)
            ->get('/admin/admin-users')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/admin-users/create')
            ->assertForbidden();

        $this->actingAs($user)
            ->delete("/admin/admin-users/{$target->id}")
            ->assertForbidden();
    }

    public function test_users_with_write_permission_can_execute_user_writes(): void
    {
        Notification::fake();

        $user = $this->createUserWithPermissions(['admin-user.write']);

        $this->actingAs($user)
            ->post('/admin/admin-users', [
                'name' => 'Writer Created',
                'email' => 'writer-created@example.com',
                'password' => 'password',
                'roles' => [],
            ])
            ->assertRedirect('/admin/admin-users');

        $created = AdminUser::where('email', 'writer-created@example.com')->firstOrFail();

        $this->actingAs($user)
            ->put("/admin/admin-users/{$created->id}", [
                'name' => 'Writer Updated',
                'email' => 'writer-updated@example.com',
                'password' => '',
                'roles' => [],
            ])
            ->assertRedirect("/admin/admin-users/{$created->id}/edit");

        $this->actingAs($user)
            ->delete("/admin/admin-users/{$created->id}")
            ->assertRedirect('/admin/admin-users');
    }

    public function test_user_validation_errors_are_returned_in_chinese(): void
    {
        $user = $this->createUserWithPermissions(['admin-user.write']);

        $this->actingAs($user)
            ->from('/admin/admin-users/create')
            ->post('/admin/admin-users', [
                'name' => '',
                'email' => '',
                'password' => '',
                'roles' => ['missing-role'],
            ])
            ->assertRedirect('/admin/admin-users/create')
            ->assertSessionHasErrors(['name', 'email', 'password', 'roles.0']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('名称 不能为空。', $errors->first('name'));
        $this->assertSame('邮箱 不能为空。', $errors->first('email'));
        $this->assertSame('密码 不能为空。', $errors->first('password'));
        $this->assertSame('管理员角色 不存在。', $errors->first('roles.0'));
    }
}
