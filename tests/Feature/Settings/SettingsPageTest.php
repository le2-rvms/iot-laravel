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
        $user = $this->createUserWithPermissions(['settings.read', 'settings-vee-validate.read', 'settings-precognition.read']);

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->has('groups', 6)
                ->where('groups', function ($groups): bool {
                    $groups = collect($groups);

                    return $groups->contains(fn (array $group) => $group['title'] === 'VeeValidate 实验室' && $group['href'] === route('vee-validate.index'))
                        && $groups->contains(fn (array $group) => $group['title'] === 'Precognition 实验室' && $group['href'] === route('precognition.index'))
                        && $groups->contains(fn (array $group) => ($group['action_label'] ?? null) === '打开 Horizon' && ($group['native'] ?? null) === true);
                }));
    }

    public function test_settings_page_hides_form_lab_entry_without_form_lab_permission(): void
    {
        $user = $this->createUserWithPermissions(['settings.read']);

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->has('groups', 4));
    }

    public function test_settings_page_only_shows_vee_validate_lab_when_precognition_permission_is_missing(): void
    {
        $user = $this->createUserWithPermissions(['settings.read', 'settings-vee-validate.read']);

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->has('groups', 5)
                ->where('groups', function ($groups): bool {
                    $groups = collect($groups);

                    return $groups->contains(fn (array $group) => $group['title'] === 'VeeValidate 实验室' && $group['href'] === route('vee-validate.index'))
                        && ! $groups->contains(fn (array $group) => $group['title'] === 'Precognition 实验室');
                }));
    }

    public function test_settings_page_only_shows_precognition_lab_when_vee_validate_permission_is_missing(): void
    {
        $user = $this->createUserWithPermissions(['settings.read', 'settings-precognition.read']);

        $this->actingAs($user)
            ->get('/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->has('groups', 5)
                ->where('groups', function ($groups): bool {
                    $groups = collect($groups);

                    return ! $groups->contains(fn (array $group) => $group['title'] === 'VeeValidate 实验室' && $group['href'] === route('vee-validate.index'))
                        && $groups->contains(fn (array $group) => $group['title'] === 'Precognition 实验室' && $group['href'] === route('precognition.index'));
                }));
    }

    public function test_users_without_settings_permission_cannot_view_the_settings_page(): void
    {
        $user = $this->createUserWithPermissions(['user.read']);

        $this->actingAs($user)
            ->get('/settings')
            ->assertForbidden();
    }

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
            ->assertSessionHas('success', '复杂表单示例提交成功。');
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
            ->assertSessionHas('success', 'Precognition 示例提交成功。');
    }

    public function test_precognition_only_returns_requested_field_errors(): void
    {
        $user = $this->createUserWithPermissions(['settings-precognition.write']);

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

    public function test_users_with_settings_permission_can_view_horizon(): void
    {
        $user = $this->createUserWithPermissions(['settings.read']);

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
}
