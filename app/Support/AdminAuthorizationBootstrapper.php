<?php

namespace App\Support;

use App\Models\Auth\AdminPermission;
use App\Models\Auth\AdminRole;
use App\Models\Auth\AdminUser;
use Spatie\Permission\PermissionRegistrar;

class AdminAuthorizationBootstrapper
{
    public function syncPermissionsAndSuperAdminRole(): AdminRole
    {
        $permissionNames = PermissionRegistry::permissionNames();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissionNames as $permissionName) {
            AdminPermission::findOrCreate($permissionName, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = AdminRole::findOrCreate(PermissionRegistry::SUPER_ADMIN_ROLE, 'web');
        $superAdmin->syncPermissions($permissionNames);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $superAdmin;
    }

    public function ensureAdminUserIsSuperAdmin(AdminUser $adminUser): void
    {
        $superAdmin = $this->syncPermissionsAndSuperAdminRole();

        if ($adminUser->hasRole($superAdmin->name)) {
            return;
        }

        $adminUser->syncRoles([$superAdmin->name]);
        $adminUser->unsetRelation('roles');
        $adminUser->unsetRelation('permissions');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
