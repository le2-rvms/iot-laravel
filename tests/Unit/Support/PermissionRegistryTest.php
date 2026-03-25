<?php

namespace Tests\Unit\Support;

use App\Http\Controllers\Web\Admin\Admin\AdminUserController;
use App\Http\Controllers\Web\Admin\Audits\AuditController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\MqttAccounts\MqttAccountController;
use App\Http\Controllers\Web\Admin\Settings\SettingsPrecognitionController;
use App\Http\Controllers\Web\Admin\Settings\SettingsVeeValidateController;
use App\Support\PermissionRegistry;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class PermissionRegistryTest extends TestCase
{
    public function test_it_loads_permissions_from_runtime_discovery(): void
    {
        $groups = collect(PermissionRegistry::groups())->keyBy('module');

        $this->assertSame('仪表盘', $groups['dashboard']['label']);
        $this->assertSame('账户密码', $groups['password']['label']);
        $this->assertSame('管理员用户', $groups['admin-user']['label']);
        $this->assertSame('审计日志', $groups['audit']['label']);
        $this->assertSame('MQTT账号管理', $groups['mqtt-account']['label']);
        $this->assertSame('应用配置', $groups['settings-application-config']['label']);
        $this->assertSame('系统配置', $groups['settings-system-config']['label']);
        $this->assertSame('复杂表单实验室', $groups['settings-vee-validate']['label']);
        $this->assertSame('Precognition 表单实验室', $groups['settings-precognition']['label']);
        $this->assertEqualsCanonicalizing(
            ['dashboard.read', 'audit.read', 'mqtt-account.read', 'mqtt-account.write', 'password.write', 'admin-role.read', 'admin-role.write', 'settings-application-config.read', 'settings-application-config.write', 'settings-precognition.read', 'settings-precognition.write', 'settings-system-config.read', 'settings-system-config.write', 'settings-vee-validate.read', 'settings-vee-validate.write', 'admin-user.read', 'admin-user.write'],
            PermissionRegistry::permissionNames(),
        );
    }

    public function test_it_returns_frontend_permission_groups_with_action_labels(): void
    {
        $groups = PermissionRegistry::groups();
        $userGroup = collect($groups)->firstWhere('module', 'admin-user');

        $this->assertSame('管理员用户', $userGroup['label']);
        $this->assertSame('读取', $userGroup['permissions'][0]['action_label']);
        $this->assertSame('写入', $userGroup['permissions'][1]['action_label']);
        $this->assertSame('仪表盘 · 读取', PermissionRegistry::displayName('dashboard.read'));
    }

    public function test_it_translates_permission_labels_for_the_current_locale_without_rebuilding_permissions(): void
    {
        App::setLocale('en');

        $groups = collect(PermissionRegistry::groups())->keyBy('module');

        $this->assertSame('Dashboard', $groups['dashboard']['label']);
        $this->assertSame('Read', $groups['admin-user']['permissions'][0]['action_label']);
        $this->assertSame('Dashboard · Read', PermissionRegistry::displayName('dashboard.read'));

        App::setLocale('zh_CN');

        $this->assertSame('仪表盘', collect(PermissionRegistry::groups())->keyBy('module')['dashboard']['label']);
        $this->assertSame('仪表盘 · 读取', PermissionRegistry::displayName('dashboard.read'));
    }

    public function test_updating_permission_translations_does_not_require_rebuilding_permissions(): void
    {
        $this->assertSame('仪表盘 · 读取', PermissionRegistry::displayName('dashboard.read'));

        Lang::addLines([
            'controllers.groups.dashboard' => '仪表盘新文案',
            'controllers.actions.read' => '查看',
        ], 'zh_CN');

        $this->assertSame('仪表盘新文案 · 查看', PermissionRegistry::displayName('dashboard.read'));
    }

    public function test_it_resolves_permission_names_for_controller_actions(): void
    {
        $this->assertSame('dashboard.read', PermissionRegistry::permissionForControllerAction(DashboardController::class, '__invoke'));
        $this->assertSame('audit.read', PermissionRegistry::permissionForControllerAction(AuditController::class, 'index'));
        $this->assertSame('mqtt-account.write', PermissionRegistry::permissionForControllerAction(MqttAccountController::class, 'store'));
        $this->assertSame('admin-user.read', PermissionRegistry::permissionForControllerAction(AdminUserController::class, 'index'));
        $this->assertSame('settings-vee-validate.write', PermissionRegistry::permissionForControllerAction(SettingsVeeValidateController::class, 'store'));
        $this->assertSame('settings-precognition.write', PermissionRegistry::permissionForControllerAction(SettingsPrecognitionController::class, 'store'));
    }
}
