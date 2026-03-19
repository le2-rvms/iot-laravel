<?php

namespace Tests\Unit\Support;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Settings\FormLabController;
use App\Http\Controllers\Users\UserController;
use App\Support\PermissionRegistry;
use Tests\TestCase;

class PermissionRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        PermissionRegistry::flushCache();

        parent::tearDown();
    }

    public function test_it_discovers_permissions_from_controller_attributes(): void
    {
        $groups = collect(PermissionRegistry::groupedForFrontend())->keyBy('module');

        $this->assertSame('仪表盘', $groups['dashboard']['label']);
        $this->assertSame('用户管理', $groups['user']['label']);
        $this->assertSame('复杂表单实验室', $groups['form-lab']['label']);
        $this->assertEqualsCanonicalizing(
            ['dashboard.read', 'form-lab.read', 'form-lab.write', 'role.read', 'role.write', 'settings.read', 'user.read', 'user.write'],
            PermissionRegistry::all(),
        );
    }

    public function test_it_returns_frontend_permission_groups_with_action_labels(): void
    {
        $groups = PermissionRegistry::groupedForFrontend();
        $userGroup = collect($groups)->firstWhere('module', 'user');

        $this->assertSame('用户管理', $userGroup['label']);
        $this->assertSame('读取', $userGroup['permissions'][0]['action_label']);
        $this->assertSame('写入', $userGroup['permissions'][1]['action_label']);
    }

    public function test_it_resolves_permission_names_for_controller_actions(): void
    {
        $this->assertSame('dashboard.read', PermissionRegistry::permissionForControllerAction(DashboardController::class, '__invoke'));
        $this->assertSame('user.read', PermissionRegistry::permissionForControllerAction(UserController::class, 'index'));
        $this->assertSame('form-lab.write', PermissionRegistry::permissionForControllerAction(FormLabController::class, 'store'));
    }
}
