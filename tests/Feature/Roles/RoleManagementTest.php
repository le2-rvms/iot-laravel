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
            ->get(route('admin-roles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AdminRole/Index')
                ->has('roles.data')
                ->where('roles.data.0.permissions', [])
                ->where('roles.data.0.permissions_count', 0)
                ->where('permissionDisplayNames', fn ($labels): bool => ($labels['dashboard.read'] ?? null) === '仪表盘 · 读取')
                ->where('auth.access', fn ($access) => ($access['admin-role.read'] ?? false) === true));
    }

    public function test_read_only_role_users_can_view_roles_but_cannot_write(): void
    {
        $user = $this->createUserWithPermissions(['admin-role.read']);

        $this->actingAs($user)
            ->get(route('admin-roles.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin-roles.create'))
            ->assertForbidden();
    }

    public function test_users_with_roles_write_permission_can_create_and_update_roles(): void
    {
        $user = $this->createUserWithPermissions(['admin-role.write']);

        $this->actingAs($user)
            ->post(route('admin-roles.store'), [
                'name' => 'Operations',
                'permissions' => ['admin-user.read', 'admin-user.write'],
            ])
            ->assertRedirect(route('admin-roles.index'));

        $role = AdminRole::findByName('Operations', 'web');

        $this->assertTrue($role->hasPermissionTo('admin-user.read'));
        $this->assertTrue($role->hasPermissionTo('admin-user.write'));

        $this->actingAs($user)
            ->put(route('admin-roles.update', $role), [
                'name' => 'Operations Updated',
                'permissions' => ['settings-system-config.read'],
            ])
            ->assertRedirect(route('admin-roles.edit', $role));

        $role = $role->fresh();

        $this->assertSame('Operations Updated', $role->name);
        $this->assertTrue($role->hasPermissionTo('settings-system-config.read'));
        $this->assertFalse($role->hasPermissionTo('admin-user.read'));
    }

    public function test_role_form_receives_permission_groups_discovered_from_controller_attributes(): void
    {
        $user = $this->createUserWithPermissions(['admin-role.write']);

        $this->actingAs($user)
            ->get(route('admin-roles.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AdminRole/Create')
                ->has('permissionGroups', 13)
                ->where('permissionGroups', function ($groups): bool {
                    $auditGroup = collect($groups)->firstWhere('module', 'audit');
                    $deviceProductGroup = collect($groups)->firstWhere('module', 'device-product');
                    $passwordGroup = collect($groups)->firstWhere('module', 'password');
                    $mqttAccountGroup = collect($groups)->firstWhere('module', 'mqtt-account');
                    $applicationGroup = collect($groups)->firstWhere('module', 'settings-application-config');
                    $systemGroup = collect($groups)->firstWhere('module', 'settings-system-config');
                    $veeValidateGroup = collect($groups)->firstWhere('module', 'settings-vee-validate');
                    $precognitionGroup = collect($groups)->firstWhere('module', 'settings-precognition');

                    return $passwordGroup !== null
                        && $auditGroup !== null
                        && $deviceProductGroup !== null
                        && $mqttAccountGroup !== null
                        && $applicationGroup !== null
                        && $systemGroup !== null
                        && $veeValidateGroup !== null
                        && $precognitionGroup !== null
                        && $auditGroup['label'] === '审计日志'
                        && $auditGroup['permissions'][0]['name'] === 'audit.read'
                        && $deviceProductGroup['label'] === '设备产品'
                        && $deviceProductGroup['permissions'][0]['name'] === 'device-product.read'
                        && $deviceProductGroup['permissions'][1]['name'] === 'device-product.write'
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
            ->from(route('admin-roles.create'))
            ->post(route('admin-roles.store'), [
                'name' => '',
                'permissions' => ['invalid.permission'],
            ])
            ->assertRedirect(route('admin-roles.create'))
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
            ->delete(route('admin-roles.destroy', $superAdminRole))
            ->assertRedirect(route('admin-roles.index'));

        $this->assertDatabaseHas($rolesTable, [
            'name' => PermissionRegistry::SUPER_ADMIN_ROLE,
        ]);
    }

    public function test_protected_super_admin_role_clears_explicit_permissions_when_updated(): void
    {
        $admin = $this->createSuperAdmin();
        $superAdminRole = AdminRole::findByName(PermissionRegistry::SUPER_ADMIN_ROLE, 'web');

        AdminPermission::findOrCreate('dashboard.read', 'web');
        AdminPermission::findOrCreate('admin-user.read', 'web');
        $superAdminRole->syncPermissions(['dashboard.read']);

        $this->actingAs($admin)
            ->put(route('admin-roles.update', $superAdminRole), [
                'name' => 'Renamed Super Admin',
                'permissions' => ['dashboard.read', 'admin-user.read'],
            ])
            ->assertRedirect(route('admin-roles.edit', $superAdminRole));

        $superAdminRole = $superAdminRole->fresh();

        $this->assertSame(PermissionRegistry::SUPER_ADMIN_ROLE, $superAdminRole->name);
        $this->assertCount(0, $superAdminRole->permissions);
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
            ->delete(route('admin-roles.destroy', $role))
            ->assertRedirect(route('admin-roles.index'));

        $this->assertDatabaseHas($rolesTable, [
            'name' => 'Bound Role',
        ]);
    }

    public function test_sync_permissions_and_super_admin_role_removes_stale_permissions_and_clears_super_admin_permissions(): void
    {
        $permissionsTable = config('permission.table_names.permissions');
        $superAdminRole = AdminRole::syncPermissionsAndSuperAdminRole();

        AdminPermission::query()->create([
            'name' => 'legacy.permission',
            'guard_name' => 'web',
        ]);
        AdminPermission::findOrCreate('dashboard.read', 'web');
        $superAdminRole->syncPermissions(['dashboard.read']);

        $superAdminRole = AdminRole::syncPermissionsAndSuperAdminRole();

        $this->assertDatabaseMissing($permissionsTable, [
            'name' => 'legacy.permission',
            'guard_name' => 'web',
        ]);
        $this->assertDatabaseHas($permissionsTable, [
            'name' => 'admin-user.read',
            'guard_name' => 'web',
        ]);
        $this->assertCount(0, $superAdminRole->permissions);
    }
}
