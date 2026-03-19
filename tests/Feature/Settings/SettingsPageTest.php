<?php

namespace Tests\Feature\Settings;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_settings_read_permission_can_view_the_settings_page(): void
    {
        $user = $this->createUserWithPermissions(['settings.read']);

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->has('groups', 5)
                ->where('groups.2.action_label', '打开 Horizon')
                ->where('groups.2.native', true)
                ->where('groups.4.action_label', '打开实验室'));
    }

    public function test_users_without_settings_permission_cannot_view_the_settings_page(): void
    {
        $user = $this->createUserWithPermissions(['users.read']);

        $this->actingAs($user)
            ->get('/settings')
            ->assertForbidden();
    }

    public function test_users_with_settings_permission_can_view_form_lab_page(): void
    {
        $user = $this->createUserWithPermissions(['settings.read']);

        $this->actingAs($user)
            ->get('/settings/form-lab')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/FormLab')
                ->has('channelTypes', 3)
                ->has('triggerModes', 3));
    }

    public function test_users_without_settings_permission_cannot_view_form_lab_page(): void
    {
        $user = $this->createUserWithPermissions(['users.read']);

        $this->actingAs($user)
            ->get('/settings/form-lab')
            ->assertForbidden();
    }

    public function test_form_lab_returns_validation_errors_for_invalid_payload(): void
    {
        $user = $this->createUserWithPermissions(['settings.read']);

        $this->actingAs($user)
            ->from('/settings/form-lab')
            ->post('/settings/form-lab', [
                'name' => '',
                'enabled' => true,
                'description' => '',
                'trigger_mode' => 'threshold',
                'threshold' => null,
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '',
                'quiet_hours_end' => '',
                'channels' => [],
            ])
            ->assertRedirect('/settings/form-lab')
            ->assertSessionHasErrors(['name', 'threshold', 'quiet_hours_start', 'channels']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('规则名称 不能为空。', $errors->first('name'));
        $this->assertSame('阈值触发模式下必须填写阈值。', $errors->first('threshold'));
    }

    public function test_form_lab_nested_attribute_labels_are_localized(): void
    {
        $user = $this->createUserWithPermissions(['settings.read']);

        $this->actingAs($user)
            ->from('/settings/form-lab')
            ->post('/settings/form-lab', [
                'name' => '规则A',
                'enabled' => true,
                'description' => '',
                'trigger_mode' => 'manual',
                'threshold' => null,
                'quiet_hours_enabled' => false,
                'quiet_hours_start' => null,
                'quiet_hours_end' => null,
                'channels' => [
                    [
                        'type' => 'email',
                        'target' => '',
                        'retries' => 1,
                        'enabled' => true,
                    ],
                ],
            ])
            ->assertRedirect('/settings/form-lab')
            ->assertSessionHasErrors(['channels.0.target']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('渠道目标 不能为空。', $errors->first('channels.0.target'));
    }

    public function test_form_lab_accepts_valid_nested_payload(): void
    {
        $user = $this->createUserWithPermissions(['settings.read']);

        $this->actingAs($user)
            ->post('/settings/form-lab', [
                'name' => 'Critical Alerts',
                'enabled' => true,
                'description' => '复杂表单提交流程验证',
                'trigger_mode' => 'threshold',
                'threshold' => 5,
                'quiet_hours_enabled' => true,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '07:00',
                'channels' => [
                    [
                        'type' => 'email',
                        'target' => 'ops@example.com',
                        'retries' => 2,
                        'enabled' => true,
                    ],
                    [
                        'type' => 'webhook',
                        'target' => 'https://example.com/hooks/alerts',
                        'retries' => 1,
                        'enabled' => false,
                    ],
                ],
            ])
            ->assertRedirect('/settings/form-lab')
            ->assertSessionHas('success', '复杂表单示例提交成功。');
    }

    public function test_users_with_settings_permission_can_view_horizon(): void
    {
        $user = $this->createUserWithPermissions(['settings.read']);

        $this->actingAs($user)
            ->get('/horizon')
            ->assertOk();
    }

    public function test_users_without_settings_permission_cannot_view_horizon(): void
    {
        $user = $this->createUserWithPermissions(['users.read']);

        $this->actingAs($user)
            ->get('/horizon')
            ->assertForbidden();
    }

    public function test_guests_are_redirected_when_visiting_horizon(): void
    {
        $this->get('/horizon')
            ->assertRedirect('/login');
    }
}
