<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\Web\Admin\Account\PasswordController;
use App\Http\Controllers\Web\Admin\Admin\AdminRoleController;
use App\Http\Controllers\Web\Admin\Admin\AdminUserController;
use App\Http\Controllers\Web\Admin\Controller;
use App\Http\Controllers\Web\Admin\Audits\AuditController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\MqttAccounts\MqttAccountController;
use App\Http\Controllers\Web\Admin\Settings\SettingsApplicationConfigController;
use App\Http\Controllers\Web\Admin\Settings\SettingsPrecognitionController;
use App\Http\Controllers\Web\Admin\Settings\SettingsSystemConfigController;
use App\Http\Controllers\Web\Admin\Settings\SettingsVeeValidateController;
use ReflectionMethod;
use Tests\TestCase;

class InertiaPageInferenceTest extends TestCase
{
    public function test_resource_controllers_infer_the_expected_page_names(): void
    {
        $this->assertSame('AdminUser/Index', $this->inertiaPage(new AdminUserController, 'index'));
        $this->assertSame('AdminUser/Create', $this->inertiaPage(new AdminUserController, 'create'));
        $this->assertSame('AdminUser/Edit', $this->inertiaPage(new AdminUserController, 'edit'));

        $this->assertSame('AdminRole/Index', $this->inertiaPage(new AdminRoleController, 'index'));
        $this->assertSame('MqttAccount/Index', $this->inertiaPage(new MqttAccountController, 'index'));
        $this->assertSame('Audit/Index', $this->inertiaPage(new AuditController, 'index'));
    }

    public function test_invoke_controllers_do_not_append_the_action_segment(): void
    {
        $this->assertSame('Dashboard', $this->inertiaPage(new DashboardController, '__invoke'));
    }

    public function test_nested_controllers_preserve_supported_page_subdirectories(): void
    {
        $this->assertSame('Password/Edit', $this->inertiaPage(new PasswordController, 'edit'));
    }

    public function test_admin_group_directory_is_preserved_during_inference(): void
    {
        $this->assertSame('AdminUser/Index', $this->inertiaPage(new AdminUserController, 'index'));
        $this->assertSame('AdminRole/Edit', $this->inertiaPage(new AdminRoleController, 'edit'));
    }

    public function test_settings_pages_follow_the_default_mapping_without_overrides(): void
    {
        $this->assertSame('SettingsApplicationConfig/Index', $this->inertiaPage(new SettingsApplicationConfigController, 'index'));
        $this->assertSame('SettingsSystemConfig/Create', $this->inertiaPage(new SettingsSystemConfigController, 'create'));
        $this->assertSame('SettingsApplicationConfig/Edit', $this->inertiaPage(new SettingsApplicationConfigController, 'edit'));
        $this->assertSame('SettingsVeeValidate/Index', $this->inertiaPage(new SettingsVeeValidateController, 'index'));
        $this->assertSame('SettingsPrecognition/Index', $this->inertiaPage(new SettingsPrecognitionController, 'index'));
    }

    private function inertiaPage(Controller $controller, string $action): string
    {
        $method = new ReflectionMethod($controller, 'inferInertiaPage');
        $method->setAccessible(true);

        $actionName = $action === '__invoke'
            ? $controller::class
            : $controller::class.'@'.$action;

        return $method->invoke($controller, $actionName);
    }
}
