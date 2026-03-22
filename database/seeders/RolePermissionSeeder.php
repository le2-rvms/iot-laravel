<?php

namespace Database\Seeders;

use App\Models\Auth\AdminPermission;
use App\Models\Auth\AdminRole;
use App\Models\Auth\AdminUser;
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
            AdminPermission::findOrCreate($permissionName, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = AdminRole::findOrCreate(PermissionRegistry::SUPER_ADMIN_ROLE, 'web');
        $superAdmin->syncPermissions($permissionNames);

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();

        if ($admin) {
            $admin->syncRoles([$superAdmin->name]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
