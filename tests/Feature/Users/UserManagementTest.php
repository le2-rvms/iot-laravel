<?php

namespace Tests\Feature\Users;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
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
            ->get(route('admin-users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AdminUser/Index')
                ->has('users.data', 4)
                ->where('filters', [])
                ->where('auth.access', fn ($access) => ($access['admin-user.read'] ?? false) === true)
                ->missing('auth.roles'));
    }

    public function test_unverified_users_cannot_access_the_users_index(): void
    {
        $admin = AdminUser::factory()->unverified()->create();

        $this->actingAs($admin)
            ->get(route('admin-users.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_users_can_view_the_dashboard(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('hero.userName', $admin->name)
                ->has('kpis', 6));
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
            ->post(route('admin-users.store'), [
                'name' => 'New User',
                'email' => 'new-user@example.com',
                'password' => 'password',
                'roles' => ['Editor'],
            ])
            ->assertRedirect(route('admin-users.index'));

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
            ->put(route('admin-users.update', $user), [
                'name' => 'Updated User',
                'email' => 'updated@example.com',
                'password' => '',
                'roles' => ['Manager'],
            ])
            ->assertRedirect(route('admin-users.edit', $user));

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
            ->delete(route('admin-users.destroy', $user))
            ->assertRedirect(route('admin-users.index'));

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
            ->get(route('admin-users.index', ['search__func' => 'alice']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AdminUser/Index')
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
            ->get(route('admin-users.index', ['name__eq' => 'Alice Cooper']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AdminUser/Index')
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
                'X-Inertia-Partial-Component' => 'AdminUser/Index',
                'X-Inertia-Partial-Data' => 'users,filters',
            ])
            ->get(route('admin-users.index', ['search__func' => 'partial']))
            ->assertOk()
            ->assertJsonPath('component', 'AdminUser/Index')
            ->assertJsonPath('props.filters.search__func', 'partial')
            ->assertJsonPath('props.users.data.0.email', 'partial@example.com');
    }

    public function test_legacy_search_query_is_rejected_with_a_validation_error(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->get(route('admin-users.index', ['search' => 'alice']))
            ->assertStatus(422)
            ->assertJsonPath('errors.search.0', '筛选条件格式无效，必须使用 field__operator 形式。');
    }

    public function test_read_only_users_can_view_users_but_cannot_modify_them(): void
    {
        $user = $this->createUserWithPermissions(['admin-user.read']);
        $target = AdminUser::factory()->create();

        $this->actingAs($user)
            ->get(route('admin-users.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin-users.create'))
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('admin-users.destroy', $target))
            ->assertForbidden();
    }

    public function test_users_with_write_permission_can_execute_user_writes(): void
    {
        Notification::fake();

        $user = $this->createUserWithPermissions(['admin-user.write']);

        $this->actingAs($user)
            ->post(route('admin-users.store'), [
                'name' => 'Writer Created',
                'email' => 'writer-created@example.com',
                'password' => 'password',
                'roles' => [],
            ])
            ->assertRedirect(route('admin-users.index'));

        $created = AdminUser::where('email', 'writer-created@example.com')->firstOrFail();

        $this->actingAs($user)
            ->put(route('admin-users.update', $created), [
                'name' => 'Writer Updated',
                'email' => 'writer-updated@example.com',
                'password' => '',
                'roles' => [],
            ])
            ->assertRedirect(route('admin-users.edit', $created));

        $this->actingAs($user)
            ->delete(route('admin-users.destroy', $created))
            ->assertRedirect(route('admin-users.index'));
    }

    public function test_user_validation_errors_are_returned_in_chinese(): void
    {
        $user = $this->createUserWithPermissions(['admin-user.write']);

        $this->actingAs($user)
            ->from(route('admin-users.create'))
            ->post(route('admin-users.store'), [
                'name' => '',
                'email' => '',
                'password' => '',
                'roles' => ['missing-role'],
            ])
            ->assertRedirect(route('admin-users.create'))
            ->assertSessionHasErrors(['name', 'email', 'password', 'roles.0']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('名称 不能为空。', $errors->first('name'));
        $this->assertSame('邮箱 不能为空。', $errors->first('email'));
        $this->assertSame('密码 不能为空。', $errors->first('password'));
        $this->assertSame('管理员角色 不存在。', $errors->first('roles.0'));
    }
}
