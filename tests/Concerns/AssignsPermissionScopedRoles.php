<?php

namespace Tests\Concerns;

use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

trait AssignsPermissionScopedRoles
{
    /**
     * @param  array<int, string>  $permissions
     */
    protected function assignPermissionsToUser(AdminUser $user, array $permissions): AdminRole
    {
        // 测试临时角色也依赖运行时权限注册表先完成同步。
        AdminRole::syncPermissionsAndSuperAdminRole();

        $role = AdminRole::create([
            // 随机角色名可避免并行测试时撞上角色名称唯一约束。
            'name' => 'Role '.Str::uuid(),
            'guard_name' => 'web',
        ]);

        // 测试通过一次性角色挂权限，确保运行时仍走真实的 Spatie 授权链路。
        $role->syncPermissions($permissions);
        $user->assignRole($role);

        $this->refreshUserAuthorizationState($user);

        return $role;
    }

    protected function refreshUserAuthorizationState(AdminUser $user): void
    {
        // 对齐生产侧的缓存重置行为，确保权限断言读取到的是最新状态。
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
