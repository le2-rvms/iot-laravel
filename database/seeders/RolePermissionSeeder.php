<?php

namespace Database\Seeders;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use App\Models\Auth\User;
use App\Support\PermissionRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionNames = PermissionRegistry::permissionNames();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissionNames as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::findOrCreate(PermissionRegistry::SUPER_ADMIN_ROLE, 'web');
        $superAdmin->syncPermissions($permissionNames);

        $admin = User::query()->where('email', 'admin@example.com')->first();

        if ($admin) {
            $admin->syncRoles([$superAdmin->name]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
