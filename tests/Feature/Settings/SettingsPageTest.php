<?php

namespace Tests\Feature\Settings;

use App\Models\Settings\Config;
use App\Values\Settings\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_form_lab_read_permission_can_view_form_lab_page(): void
    {
        $user = $this->createUserWithPermissions(['settings-vee-validate.read']);

        $this->actingAs($user)
            ->get('/settings/vee-validate')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/FormLab')
                ->has('channelTypes', 3)
                ->has('triggerModes', 3));
    }

    public function test_users_with_form_lab_read_permission_can_view_precognition_form_lab_page(): void
    {
        $user = $this->createUserWithPermissions(['settings-precognition.read']);

        $this->actingAs($user)
            ->get(route('precognition.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/FormLabPrecognition')
                ->has('channelTypes', 3));
    }

    public function test_users_without_form_lab_read_permission_cannot_view_form_lab_page(): void
    {
        $user = $this->createUserWithPermissions(['user.read']);

        $this->actingAs($user)
            ->get('/settings/vee-validate')
            ->assertForbidden();
    }

    public function test_users_without_form_lab_read_permission_cannot_view_precognition_form_lab_page(): void
    {
        $user = $this->createUserWithPermissions(['user.read']);

        $this->actingAs($user)
            ->get(route('precognition.index'))
            ->assertForbidden();
    }

    public function test_form_lab_returns_validation_errors_for_invalid_payload(): void
    {
        $user = $this->createUserWithPermissions(['settings-vee-validate.write']);

        $this->actingAs($user)
            ->from('/settings/vee-validate')
            ->post('/settings/vee-validate', [
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
            ->assertRedirect('/settings/vee-validate')
            ->assertSessionHasErrors(['name', 'threshold', 'quiet_hours_start', 'channels']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('规则名称 不能为空。', $errors->first('name'));
        $this->assertSame('阈值触发模式下必须填写阈值。', $errors->first('threshold'));
    }

    public function test_form_lab_nested_attribute_labels_are_localized(): void
    {
        $user = $this->createUserWithPermissions(['settings-vee-validate.write']);

        $this->actingAs($user)
            ->from('/settings/vee-validate')
            ->post('/settings/vee-validate', [
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
            ->assertRedirect('/settings/vee-validate')
            ->assertSessionHasErrors(['channels.0.target']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('渠道目标 不能为空。', $errors->first('channels.0.target'));
    }

    public function test_form_lab_accepts_valid_nested_payload(): void
    {
        $user = $this->createUserWithPermissions(['settings-vee-validate.write']);

        $this->actingAs($user)
            ->post('/settings/vee-validate', [
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
            ->assertRedirect('/settings/vee-validate')
            ->assertSessionHas('success', '规则内容已提交。');
    }

    public function test_precognition_form_returns_validation_errors_for_invalid_payload(): void
    {
        $user = $this->createUserWithPermissions(['settings-precognition.write']);

        $this->actingAs($user)
            ->from(route('precognition.index'))
            ->post(route('precognition.store'), [
                'name' => '',
                'email' => '',
                'channel' => 'email',
                'target' => '',
                'daily_limit' => null,
                'notes' => '',
            ])
            ->assertRedirect(route('precognition.index'))
            ->assertSessionHasErrors(['name', 'email', 'target', 'daily_limit']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('规则名称 不能为空。', $errors->first('name'));
        $this->assertSame('邮箱 不能为空。', $errors->first('email'));
        $this->assertSame('渠道目标 不能为空。', $errors->first('target'));
        $this->assertSame('每日上限 不能为空。', $errors->first('daily_limit'));
    }

    public function test_precognition_form_accepts_valid_payload(): void
    {
        $user = $this->createUserWithPermissions(['settings-precognition.write']);

        $this->actingAs($user)
            ->post(route('precognition.store'), [
                'name' => 'Precognition Demo',
                'email' => 'ops@example.com',
                'channel' => 'webhook',
                'target' => 'https://example.com/hooks/ops',
                'daily_limit' => 10,
                'notes' => '服务端实时预校验示例',
            ])
            ->assertRedirect(route('precognition.index'))
            ->assertSessionHas('success', '规则内容已提交。');
    }

    public function test_precognition_only_returns_requested_field_errors(): void
    {
        $user = $this->createUserWithPermissions(['settings-precognition.write']);

        // 这里锁的是 Precognition 的按字段预校验契约，避免一次请求把整张表单错误都提前返回。
        $response = $this->actingAs($user)
            ->withPrecognition()
            ->postJson(route('precognition.store'), [
                'name' => '',
                'email' => '',
                'channel' => 'email',
                'target' => 'not-an-email',
                'daily_limit' => null,
                'notes' => '',
            ], [
                'Precognition-Validate-Only' => 'target',
            ]);

        $response->assertStatus(422);
        $response->assertHeader('Precognition', 'true');
        $response->assertJsonPath('errors.target.0', 'Email 渠道必须填写合法邮箱地址。');
        $response->assertJsonMissingPath('errors.name');
        $response->assertJsonMissingPath('errors.email');
        $response->assertJsonMissingPath('errors.daily_limit');
    }

    public function test_precognition_name_validation_does_not_return_target_errors(): void
    {
        $user = $this->createUserWithPermissions(['settings-precognition.write']);

        $response = $this->actingAs($user)
            ->withPrecognition()
            ->postJson(route('precognition.store'), [
                'name' => '',
                'email' => '',
                'channel' => 'email',
                'target' => '',
                'daily_limit' => null,
                'notes' => '',
            ], [
                'Precognition-Validate-Only' => 'name',
            ]);

        $response->assertStatus(422);
        $response->assertHeader('Precognition', 'true');
        $response->assertJsonPath('errors.name.0', '规则名称 不能为空。');
        $response->assertJsonMissingPath('errors.target');
        $response->assertJsonMissingPath('errors.email');
        $response->assertJsonMissingPath('errors.daily_limit');
    }

    public function test_precognition_returns_success_for_valid_requested_field(): void
    {
        $user = $this->createUserWithPermissions(['settings-precognition.write']);

        $this->actingAs($user)
            ->withPrecognition()
            ->postJson('/settings/precognition', [
                'name' => 'Precognition Demo',
                'email' => 'ops@example.com',
                'channel' => 'webhook',
                'target' => 'https://example.com/hooks/ops',
                'daily_limit' => 10,
                'notes' => '',
            ], [
                'Precognition-Validate-Only' => 'target',
            ])
            ->assertSuccessfulPrecognition();
    }

    public function test_precognition_applies_channel_specific_target_rules(): void
    {
        $user = $this->createUserWithPermissions(['settings-precognition.write']);

        $cases = [
            ['channel' => 'email', 'target' => 'invalid-target', 'message' => 'Email 渠道必须填写合法邮箱地址。'],
            ['channel' => 'webhook', 'target' => 'invalid-target', 'message' => 'Webhook 渠道必须填写合法 URL。'],
            ['channel' => 'sms', 'target' => 'invalid-target', 'message' => 'SMS 渠道必须填写合法手机号。'],
        ];

        foreach ($cases as $case) {
            $response = $this->actingAs($user)
                ->withPrecognition()
                ->postJson(route('precognition.store'), [
                    'name' => 'Precognition Demo',
                    'email' => 'ops@example.com',
                    'channel' => $case['channel'],
                    'target' => $case['target'],
                    'daily_limit' => 10,
                    'notes' => '',
                ], [
                    'Precognition-Validate-Only' => 'target',
                ]);

            $response->assertStatus(422);
            $response->assertJsonPath('errors.target.0', $case['message']);
        }
    }

    public function test_users_with_system_config_permission_can_view_horizon(): void
    {
        $user = $this->createUserWithPermissions(['settings-system-config.read']);

        $this->actingAs($user)
            ->get('/horizon')
            ->assertOk();
    }

    public function test_users_without_settings_permission_cannot_view_horizon(): void
    {
        $user = $this->createUserWithPermissions(['user.read']);

        $this->actingAs($user)
            ->get('/horizon')
            ->assertForbidden();
    }

    public function test_guests_are_redirected_when_visiting_horizon(): void
    {
        $this->get('/horizon')
            ->assertRedirect('/login');
    }

    public function test_users_with_application_config_read_permission_can_view_the_application_config_index(): void
    {
        Config::query()->create([
            'key' => 'app.name',
            'value' => 'IoT Admin',
            'category' => Category::APPLICATION,
            'is_masked' => false,
            'remark' => '应用名称',
        ]);

        $user = $this->createUserWithPermissions(['settings-application-config.read']);

        $this->actingAs($user)
            ->get(route('application-configs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Configs/Index')
                ->where('category', Category::APPLICATION)
                ->where('configs.data.0.key', 'app.name')
                ->where('configs.data.0.value_display', 'IoT Admin'));
    }

    public function test_users_with_system_config_read_permission_can_view_the_system_config_index(): void
    {
        Config::query()->create([
            'key' => 'system.notice',
            'value' => '维护中',
            'category' => Category::SYSTEM,
            'is_masked' => false,
            'remark' => '系统公告',
        ]);

        $user = $this->createUserWithPermissions(['settings-system-config.read']);

        $this->actingAs($user)
            ->get(route('system-configs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Configs/Index')
                ->where('category', Category::SYSTEM)
                ->where('configs.data.0.key', 'system.notice'));
    }

    public function test_users_without_application_config_permission_cannot_view_the_application_config_index(): void
    {
        $user = $this->createUserWithPermissions(['user.read']);

        $this->actingAs($user)
            ->get(route('application-configs.index'))
            ->assertForbidden();
    }

    public function test_users_without_system_config_permission_cannot_view_the_system_config_index(): void
    {
        $user = $this->createUserWithPermissions(['user.read']);

        $this->actingAs($user)
            ->get(route('system-configs.index'))
            ->assertForbidden();
    }

    public function test_setting_write_permissions_control_create_edit_and_mutations(): void
    {
        $applicationConfig = Config::query()->create([
            'key' => 'app.secret',
            'value' => 'token',
            'category' => Category::APPLICATION,
            'is_masked' => true,
            'remark' => '应用密钥',
        ]);
        $readOnlyUser = $this->createUserWithPermissions(['settings-application-config.read']);
        $writeUser = $this->createUserWithPermissions(['settings-application-config.write']);

        $this->actingAs($readOnlyUser)
            ->get(route('application-configs.create'))
            ->assertForbidden();

        $this->actingAs($readOnlyUser)
            ->post(route('application-configs.store'), [
                'key' => 'app.locale',
                'value' => 'zh_CN',
                'is_masked' => false,
                'remark' => '默认语言',
            ])
            ->assertForbidden();

        $this->actingAs($writeUser)
            ->get(route('application-configs.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Configs/Create')
                ->where('category', Category::APPLICATION));

        $this->actingAs($writeUser)
            ->get(route('application-configs.edit', $applicationConfig))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Configs/Edit')
                ->where('config.key', 'app.secret'));
    }

    public function test_application_config_list_search_only_matches_key_and_remark(): void
    {
        Config::query()->create([
            'key' => 'app.name',
            'value' => 'IoT Admin',
            'category' => Category::APPLICATION,
            'is_masked' => false,
            'remark' => '应用名称',
        ]);
        Config::query()->create([
            'key' => 'app.secret',
            'value' => 'token-123',
            'category' => Category::APPLICATION,
            'is_masked' => true,
            'remark' => '鉴权密钥',
        ]);

        $user = $this->createUserWithPermissions(['settings-application-config.read']);

        $this->actingAs($user)
            ->get(route('application-configs.index', ['search' => '密钥']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Configs/Index')
                ->has('configs.data', 1)
                ->where('configs.data.0.key', 'app.secret')
                ->where('configs.data.0.value_display', '*****'));
    }

    public function test_application_config_list_search_is_case_insensitive(): void
    {
        Config::query()->create([
            'key' => 'GatewayTimeout',
            'value' => '30',
            'category' => Category::APPLICATION,
            'is_masked' => false,
            'remark' => '网关超时设置',
        ]);
        Config::query()->create([
            'key' => 'sensor.timeout',
            'value' => '10',
            'category' => Category::APPLICATION,
            'is_masked' => false,
            'remark' => '传感器超时设置',
        ]);

        $user = $this->createUserWithPermissions(['settings-application-config.read']);

        $this->actingAs($user)
            ->get(route('application-configs.index', ['search' => 'gateway']))
            ->assertOk()
            // 这里锁的是 PostgreSQL 目标环境下的大小写不敏感搜索行为。
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Configs/Index')
                ->where('filters.search', 'gateway')
                ->has('configs.data', 1)
                ->where('configs.data.0.key', 'GatewayTimeout'));
    }

    public function test_application_config_can_be_created_with_write_permission(): void
    {
        $user = $this->createUserWithPermissions(['settings-application-config.write']);

        $this->actingAs($user)
            ->post(route('application-configs.store'), [
                'key' => 'app.theme',
                'value' => 'neutral',
                'is_masked' => false,
                'remark' => '默认主题',
            ])
            ->assertRedirect(route('application-configs.index'))
            ->assertSessionHas('success', '应用配置已创建。');

        $this->assertDatabaseHas('configs', [
            'key' => 'app.theme',
            'category' => Category::APPLICATION,
        ]);
    }

    public function test_application_config_store_supports_precognition_for_requested_field_errors(): void
    {
        $user = $this->createUserWithPermissions(['settings-application-config.write']);

        $response = $this->actingAs($user)
            ->withPrecognition()
            ->postJson(route('application-configs.store'), [
                'key' => '',
                'value' => '',
                'is_masked' => false,
                'remark' => '',
            ], [
                'Precognition-Validate-Only' => 'key',
            ]);

        $response->assertStatus(422);
        $response->assertHeader('Precognition', 'true');
        $response->assertJsonPath('errors.key.0', '配置键 不能为空。');
        $response->assertJsonMissingPath('errors.value');
        $response->assertJsonMissingPath('errors.remark');
    }

    public function test_system_config_can_be_updated_with_write_permission(): void
    {
        $config = Config::query()->create([
            'key' => 'system.notice',
            'value' => '旧公告',
            'category' => Category::SYSTEM,
            'is_masked' => false,
            'remark' => '系统公告',
        ]);
        $user = $this->createUserWithPermissions(['settings-system-config.write']);

        $this->actingAs($user)
            ->put(route('system-configs.update', $config), [
                'key' => 'system.notice',
                'value' => '新公告',
                'is_masked' => true,
                'remark' => '新的系统公告',
            ])
            ->assertRedirect(route('system-configs.index'))
            ->assertSessionHas('success', '系统配置已更新。');

        $this->assertDatabaseHas('configs', [
            'id' => $config->id,
            'value' => '新公告',
            'is_masked' => true,
        ]);
    }

    public function test_system_config_update_supports_successful_precognition_validation(): void
    {
        $config = Config::query()->create([
            'key' => 'system.notice',
            'value' => '旧公告',
            'category' => Category::SYSTEM,
            'is_masked' => false,
            'remark' => '系统公告',
        ]);
        $user = $this->createUserWithPermissions(['settings-system-config.write']);

        $this->actingAs($user)
            ->withPrecognition()
            ->putJson(route('system-configs.update', $config), [
                'key' => 'system.notice',
                'value' => '新公告',
                'is_masked' => true,
                'remark' => '新的系统公告',
            ], [
                'Precognition-Validate-Only' => 'value',
            ])
            ->assertSuccessfulPrecognition();
    }

    public function test_application_config_can_be_deleted_with_write_permission(): void
    {
        $config = Config::query()->create([
            'key' => 'app.locale',
            'value' => 'zh_CN',
            'category' => Category::APPLICATION,
            'is_masked' => false,
            'remark' => '默认语言',
        ]);
        $user = $this->createUserWithPermissions(['settings-application-config.write']);

        $this->actingAs($user)
            ->delete(route('application-configs.destroy', $config))
            ->assertRedirect(route('application-configs.index'))
            ->assertSessionHas('success', '应用配置已删除。');

        $this->assertDatabaseMissing('configs', [
            'id' => $config->id,
        ]);
    }

    public function test_category_mismatch_returns_not_found_for_edit_update_and_destroy(): void
    {
        $config = Config::query()->create([
            'key' => 'system.secret',
            'value' => '123',
            'category' => Category::SYSTEM,
            'is_masked' => true,
            'remark' => '系统密钥',
        ]);
        $user = $this->createUserWithPermissions(['settings-application-config.write']);

        $this->actingAs($user)
            ->get(route('application-configs.edit', $config))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(route('application-configs.update', $config), [
                'key' => 'system.secret',
                'value' => '456',
                'is_masked' => true,
                'remark' => '系统密钥',
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->delete(route('application-configs.destroy', $config))
            ->assertNotFound();
    }

    public function test_setting_validation_errors_are_returned_in_chinese(): void
    {
        $user = $this->createUserWithPermissions(['settings-application-config.write']);

        $this->actingAs($user)
            ->from(route('application-configs.create'))
            ->post(route('application-configs.store'), [
                'key' => '',
                'value' => '',
                'is_masked' => '',
                'remark' => '',
            ])
            ->assertRedirect(route('application-configs.create'))
            ->assertSessionHasErrors(['key', 'value', 'is_masked', 'remark']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('配置键 不能为空。', $errors->first('key'));
        $this->assertSame('配置值 不能为空。', $errors->first('value'));
        $this->assertSame('是否打码 不能为空。', $errors->first('is_masked'));
        $this->assertSame('备注 不能为空。', $errors->first('remark'));
    }
}
