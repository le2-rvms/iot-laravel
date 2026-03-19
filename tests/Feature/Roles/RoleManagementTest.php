<?php

namespace Tests\Feature\Roles;

use App\Support\PermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
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
                ->where('auth.access', fn ($access) => ($access['roles.read'] ?? false) === true));
    }

    public function test_read_only_role_users_can_view_roles_but_cannot_write(): void
    {
        $user = $this->createUserWithPermissions(['roles.read']);

        $this->actingAs($user)
            ->get('/roles')
            ->assertOk();

        $this->actingAs($user)
            ->get('/roles/create')
            ->assertForbidden();
    }

    public function test_users_with_roles_write_permission_can_create_and_update_roles(): void
    {
        $user = $this->createUserWithPermissions(['roles.write']);

        $this->actingAs($user)
            ->post('/roles', [
                'name' => 'Operations',
                'permissions' => ['users.read', 'users.write'],
            ])
            ->assertRedirect('/roles');

        $role = Role::findByName('Operations', 'web');

        $this->assertTrue($role->hasPermissionTo('users.read'));
        $this->assertTrue($role->hasPermissionTo('users.write'));

        $this->actingAs($user)
            ->put("/roles/{$role->id}", [
                'name' => 'Operations Updated',
                'permissions' => ['settings.read'],
            ])
            ->assertRedirect("/roles/{$role->id}/edit");

        $role = $role->fresh();

        $this->assertSame('Operations Updated', $role->name);
        $this->assertTrue($role->hasPermissionTo('settings.read'));
        $this->assertFalse($role->hasPermissionTo('users.read'));
    }

    public function test_super_admin_role_cannot_be_deleted(): void
    {
        $admin = $this->createSuperAdmin();
        $superAdminRole = Role::findByName(PermissionRegistry::superAdminRole(), 'web');

        $this->actingAs($admin)
            ->delete("/roles/{$superAdminRole->id}")
            ->assertRedirect('/roles');

        $this->assertDatabaseHas('roles', [
            'name' => PermissionRegistry::superAdminRole(),
        ]);
    }

    public function test_roles_with_bound_users_cannot_be_deleted(): void
    {
        $admin = $this->createSuperAdmin();
        $role = Role::create([
            'name' => 'Bound Role',
            'guard_name' => 'web',
        ]);
        $user = $this->createUserWithPermissions(['users.read']);
        $user->syncRoles([$role->name]);

        $this->actingAs($admin)
            ->delete("/roles/{$role->id}")
            ->assertRedirect('/roles');

        $this->assertDatabaseHas('roles', [
            'name' => 'Bound Role',
        ]);
    }
}
