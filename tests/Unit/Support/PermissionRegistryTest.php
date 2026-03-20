<?php

namespace Tests\Unit\Support;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Settings\SettingsPrecognitionController;
use App\Http\Controllers\Settings\SettingsVeeValidateController;
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
        $this->assertSame('复杂表单实验室', $groups['settings-vee-validate']['label']);
        $this->assertSame('Precognition 表单实验室', $groups['settings-precognition']['label']);
        $this->assertEqualsCanonicalizing(
            ['dashboard.read', 'role.read', 'role.write', 'settings-precognition.read', 'settings-precognition.write', 'settings-vee-validate.read', 'settings-vee-validate.write', 'settings.read', 'user.read', 'user.write'],
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
        $this->assertSame('settings-vee-validate.write', PermissionRegistry::permissionForControllerAction(SettingsVeeValidateController::class, 'store'));
        $this->assertSame('settings-precognition.write', PermissionRegistry::permissionForControllerAction(SettingsPrecognitionController::class, 'store'));
    }
}
