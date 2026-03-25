<?php

namespace Tests\Feature\Roles;

use App\Models\Admin\AdminPermission;
use App\Models\Admin\AdminRole;
use App\Support\PermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_roles_index(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->get('/admin/admin-roles')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AdminRoles/Index')
                ->has('roles.data')
                ->where('roles.data.0.permissions', fn ($permissions): bool => collect($permissions)->pluck('name')->contains('dashboard.read'))
                ->where('permissionDisplayNames', fn ($labels): bool => ($labels['dashboard.read'] ?? null) === '仪表盘 · 读取')
                ->where('auth.access', fn ($access) => ($access['admin-role.read'] ?? false) === true));
    }

    public function test_read_only_role_users_can_view_roles_but_cannot_write(): void
    {
        $user = $this->createUserWithPermissions(['admin-role.read']);

        $this->actingAs($user)
            ->get('/admin/admin-roles')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/admin-roles/create')
            ->assertForbidden();
    }

    public function test_users_with_roles_write_permission_can_create_and_update_roles(): void
    {
        $user = $this->createUserWithPermissions(['admin-role.write']);

        $this->actingAs($user)
            ->post('/admin/admin-roles', [
                'name' => 'Operations',
                'permissions' => ['admin-user.read', 'admin-user.write'],
            ])
            ->assertRedirect('/admin/admin-roles');

        $role = AdminRole::findByName('Operations', 'web');

        $this->assertTrue($role->hasPermissionTo('admin-user.read'));
        $this->assertTrue($role->hasPermissionTo('admin-user.write'));

        $this->actingAs($user)
            ->put("/admin/admin-roles/{$role->id}", [
                'name' => 'Operations Updated',
                'permissions' => ['settings-system-config.read'],
            ])
            ->assertRedirect("/admin/admin-roles/{$role->id}/edit");

        $role = $role->fresh();

        $this->assertSame('Operations Updated', $role->name);
        $this->assertTrue($role->hasPermissionTo('settings-system-config.read'));
        $this->assertFalse($role->hasPermissionTo('admin-user.read'));
    }

    public function test_role_form_receives_permission_groups_discovered_from_controller_attributes(): void
    {
        $user = $this->createUserWithPermissions(['admin-role.write']);

        $this->actingAs($user)
            ->get('/admin/admin-roles/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AdminRoles/Create')
                ->has('permissionGroups', 10)
                ->where('permissionGroups', function ($groups): bool {
                    $auditGroup = collect($groups)->firstWhere('module', 'audit');
                    $passwordGroup = collect($groups)->firstWhere('module', 'password');
                    $mqttAccountGroup = collect($groups)->firstWhere('module', 'mqtt-account');
                    $applicationGroup = collect($groups)->firstWhere('module', 'settings-application-config');
                    $systemGroup = collect($groups)->firstWhere('module', 'settings-system-config');
                    $veeValidateGroup = collect($groups)->firstWhere('module', 'settings-vee-validate');
                    $precognitionGroup = collect($groups)->firstWhere('module', 'settings-precognition');

                    return $passwordGroup !== null
                        && $auditGroup !== null
                        && $mqttAccountGroup !== null
                        && $applicationGroup !== null
                        && $systemGroup !== null
                        && $veeValidateGroup !== null
                        && $precognitionGroup !== null
                        && $auditGroup['label'] === '审计日志'
                        && $auditGroup['permissions'][0]['name'] === 'audit.read'
                        && $passwordGroup['label'] === '账户密码'
                        && $passwordGroup['permissions'][0]['name'] === 'password.write'
                        && $mqttAccountGroup['label'] === 'MQTT账号管理'
                        && $mqttAccountGroup['permissions'][0]['name'] === 'mqtt-account.read'
                        && $mqttAccountGroup['permissions'][1]['name'] === 'mqtt-account.write'
                        && $applicationGroup['label'] === '应用配置'
                        && $applicationGroup['permissions'][0]['name'] === 'settings-application-config.read'
                        && $applicationGroup['permissions'][1]['name'] === 'settings-application-config.write'
                        && $systemGroup['label'] === '系统配置'
                        && $systemGroup['permissions'][0]['name'] === 'settings-system-config.read'
                        && $systemGroup['permissions'][1]['name'] === 'settings-system-config.write'
                        && $veeValidateGroup['label'] === '复杂表单实验室'
                        && $veeValidateGroup['permissions'][0]['name'] === 'settings-vee-validate.read'
                        && $veeValidateGroup['permissions'][1]['name'] === 'settings-vee-validate.write'
                        && $precognitionGroup['label'] === 'Precognition 表单实验室'
                        && $precognitionGroup['permissions'][0]['name'] === 'settings-precognition.read'
                        && $precognitionGroup['permissions'][1]['name'] === 'settings-precognition.write';
                }));
    }

    public function test_role_validation_errors_are_returned_in_chinese(): void
    {
        $user = $this->createUserWithPermissions(['admin-role.write']);

        $this->actingAs($user)
            ->from('/admin/admin-roles/create')
            ->post('/admin/admin-roles', [
                'name' => '',
                'permissions' => ['invalid.permission'],
            ])
            ->assertRedirect('/admin/admin-roles/create')
            ->assertSessionHasErrors(['name', 'permissions.0']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('管理员角色名称 不能为空。', $errors->first('name'));
        $this->assertSame('管理员权限名称 不存在。', $errors->first('permissions.0'));
    }

    public function test_super_admin_role_cannot_be_deleted(): void
    {
        $admin = $this->createSuperAdmin();
        $superAdminRole = AdminRole::findByName(PermissionRegistry::SUPER_ADMIN_ROLE, 'web');
        $rolesTable = config('permission.table_names.roles');

        $this->actingAs($admin)
            ->delete("/admin/admin-roles/{$superAdminRole->id}")
            ->assertRedirect('/admin/admin-roles');

        $this->assertDatabaseHas($rolesTable, [
            'name' => PermissionRegistry::SUPER_ADMIN_ROLE,
        ]);
    }

    public function test_roles_with_bound_users_cannot_be_deleted(): void
    {
        $admin = $this->createSuperAdmin();
        $rolesTable = config('permission.table_names.roles');
        $role = AdminRole::create([
            'name' => 'Bound Role',
            'guard_name' => 'web',
        ]);
        $user = $this->createUserWithPermissions(['admin-user.read']);
        $user->syncRoles([$role->name]);

        $this->actingAs($admin)
            ->delete("/admin/admin-roles/{$role->id}")
            ->assertRedirect('/admin/admin-roles');

        $this->assertDatabaseHas($rolesTable, [
            'name' => 'Bound Role',
        ]);
    }

    public function test_sync_permissions_and_super_admin_role_removes_stale_permissions(): void
    {
        $permissionsTable = config('permission.table_names.permissions');

        AdminPermission::query()->create([
            'name' => 'legacy.permission',
            'guard_name' => 'web',
        ]);

        AdminRole::syncPermissionsAndSuperAdminRole();

        $this->assertDatabaseMissing($permissionsTable, [
            'name' => 'legacy.permission',
            'guard_name' => 'web',
        ]);
        $this->assertDatabaseHas($permissionsTable, [
            'name' => 'admin-user.read',
            'guard_name' => 'web',
        ]);
    }
}
