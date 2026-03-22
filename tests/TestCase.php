<?php

namespace Tests;

use App\Models\Auth\AdminRole;
use App\Models\Auth\AdminUser;
use App\Support\PermissionRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use Tests\Concerns\ResetsPermissionRegistryCache;

abstract class TestCase extends BaseTestCase
{
    use ResetsPermissionRegistryCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetPermissionRegistryCache();
    }

    protected function seedPermissions(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createSuperAdmin(array $attributes = []): AdminUser
    {
        $this->seedPermissions();

        $user = AdminUser::factory()->create($attributes);
        $user->assignRole(PermissionRegistry::SUPER_ADMIN_ROLE);

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function createUserWithPermissions(array $permissions, array $attributes = []): AdminUser
    {
        $this->seedPermissions();

        $user = AdminUser::factory()->create($attributes);
        $role = AdminRole::create([
            'name' => 'Role '.Str::uuid(),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($permissions);
        $user->assignRole($role);

        return $user;
    }
}
