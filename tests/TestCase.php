<?php

namespace Tests;

use App\Models\Auth\Role;
use App\Models\Auth\User;
use App\Support\PermissionRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function seedPermissions(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createSuperAdmin(array $attributes = []): User
    {
        $this->seedPermissions();

        $user = User::factory()->create($attributes);
        $user->assignRole(PermissionRegistry::superAdminRole());

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function createUserWithPermissions(array $permissions, array $attributes = []): User
    {
        $this->seedPermissions();

        $user = User::factory()->create($attributes);
        $role = Role::create([
            'name' => 'Role '.Str::uuid(),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions);
        $user->assignRole($role);

        return $user;
    }
}
