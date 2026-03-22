<?php

namespace Database\Seeders;

use App\Models\Auth\AdminUser;
use App\Support\AdminAuthorizationBootstrapper;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $bootstrapper = app(AdminAuthorizationBootstrapper::class);

        $admin = AdminUser::query()->where('email', 'admin@example.com')->first();

        if ($admin) {
            $bootstrapper->ensureAdminUserIsSuperAdmin($admin);
            return;
        }

        $bootstrapper->syncPermissionsAndSuperAdminRole();
    }
}
