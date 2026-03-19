<?php

namespace Tests\Feature\Roles;

use App\Models\Auth\Role;
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
            ->get('/roles')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Roles/Index')
                ->has('roles.data')
                ->where('auth.access', fn ($access) => ($access['role.read'] ?? false) === true));
    }

    public function test_read_only_role_users_can_view_roles_but_cannot_write(): void
    {
        $user = $this->createUserWithPermissions(['role.read']);

        $this->actingAs($user)
            ->get('/roles')
            ->assertOk();

        $this->actingAs($user)
            ->get('/roles/create')
            ->assertForbidden();
    }

    public function test_users_with_roles_write_permission_can_create_and_update_roles(): void
    {
        $user = $this->createUserWithPermissions(['role.write']);

        $this->actingAs($user)
            ->post('/roles', [
                'name' => 'Operations',
                'permissions' => ['user.read', 'user.write'],
            ])
            ->assertRedirect('/roles');

        $role = Role::findByName('Operations', 'web');

        $this->assertTrue($role->hasPermissionTo('user.read'));
        $this->assertTrue($role->hasPermissionTo('user.write'));

        $this->actingAs($user)
            ->put("/roles/{$role->id}", [
                'name' => 'Operations Updated',
                'permissions' => ['settings.read'],
            ])
            ->assertRedirect("/roles/{$role->id}/edit");

        $role = $role->fresh();

        $this->assertSame('Operations Updated', $role->name);
        $this->assertTrue($role->hasPermissionTo('settings.read'));
        $this->assertFalse($role->hasPermissionTo('user.read'));
    }

    public function test_role_form_receives_permission_groups_discovered_from_controller_attributes(): void
    {
        $user = $this->createUserWithPermissions(['role.write']);

        $this->actingAs($user)
            ->get('/roles/create')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Roles/Create')
                ->has('permissionGroups', 5)
                ->where('permissionGroups', function ($groups): bool {
                    $formLabGroup = collect($groups)->firstWhere('module', 'form-lab');

                    return $formLabGroup !== null
                        && $formLabGroup['label'] === '复杂表单实验室'
                        && $formLabGroup['permissions'][0]['name'] === 'form-lab.read'
                        && $formLabGroup['permissions'][1]['name'] === 'form-lab.write';
                }));
    }

    public function test_role_validation_errors_are_returned_in_chinese(): void
    {
        $user = $this->createUserWithPermissions(['role.write']);

        $this->actingAs($user)
            ->from('/roles/create')
            ->post('/roles', [
                'name' => '',
                'permissions' => ['invalid.permission'],
            ])
            ->assertRedirect('/roles/create')
            ->assertSessionHasErrors(['name', 'permissions.0']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('角色名称 不能为空。', $errors->first('name'));
        $this->assertSame('权限名称 不存在。', $errors->first('permissions.0'));
    }

    public function test_super_admin_role_cannot_be_deleted(): void
    {
        $admin = $this->createSuperAdmin();
        $superAdminRole = Role::findByName(PermissionRegistry::superAdminRole(), 'web');
        $rolesTable = config('permission.table_names.roles');

        $this->actingAs($admin)
            ->delete("/roles/{$superAdminRole->id}")
            ->assertRedirect('/roles');

        $this->assertDatabaseHas($rolesTable, [
            'name' => PermissionRegistry::superAdminRole(),
        ]);
    }

    public function test_roles_with_bound_users_cannot_be_deleted(): void
    {
        $admin = $this->createSuperAdmin();
        $rolesTable = config('permission.table_names.roles');
        $role = Role::create([
            'name' => 'Bound Role',
            'guard_name' => 'web',
        ]);
        $user = $this->createUserWithPermissions(['user.read']);
        $user->syncRoles([$role->name]);

        $this->actingAs($admin)
            ->delete("/roles/{$role->id}")
            ->assertRedirect('/roles');

        $this->assertDatabaseHas($rolesTable, [
            'name' => 'Bound Role',
        ]);
    }
}
