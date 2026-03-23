<?php

namespace Tests;

use App\Models\Auth\AdminUser;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\AssignsPermissionScopedRoles;
use Tests\Concerns\ResetsPermissionRegistryCache;

abstract class TestCase extends BaseTestCase
{
    // 权限相关测试辅助统一收口在这里，保证各类 Feature 用例都从同一套授权原语出发。
    use AssignsPermissionScopedRoles, ResetsPermissionRegistryCache;

    protected function setUp(): void
    {
        parent::setUp();

        // 权限发现结果按 PHP 进程做静态缓存，因此每个测试开始前都要清掉。
        $this->resetPermissionRegistryCache();
    }

    protected function createSuperAdmin(array $attributes = []): AdminUser
    {
        $user = AdminUser::factory()->create($attributes);

        // 测试里的超级管理员也走和生产代码一致的模型方法。
        $user->assignSuperAdminRole();

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function createUserWithPermissions(array $permissions, array $attributes = []): AdminUser
    {
        $user = AdminUser::factory()->create($attributes);

        // 带权限的测试用户通过一次性角色组装，确保授权链路仍然接近真实运行时。
        $this->assignPermissionsToUser($user, $permissions);

        return $user;
    }
}
