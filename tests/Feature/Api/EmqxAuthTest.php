<?php

namespace Tests\Feature\Api;

use App\Models\Iot\IotMqttAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmqxAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_failures_return_ignore_contract(): void
    {
        // 这里锁的是 EMQX 对接契约，不是 Laravel 默认验证行为。
        $this->postJson('/api/emqx/auth', [])
            ->assertStatus(400)
            ->assertJsonPath('result', 'ignore')
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_overlong_clientid_returns_ignore_contract(): void
    {
        // 这里锁住的是接口长度约束已和后台管理/表结构收齐到 50。
        $this->postJson('/api/emqx/auth', [
            'username' => 'mqtt-client',
            'password' => 'secret',
            'clientid' => str_repeat('c', 51),
        ])
            ->assertStatus(400)
            ->assertJsonPath('result', 'ignore')
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonValidationErrors(['clientid']);
    }

    public function test_unknown_accounts_are_denied(): void
    {
        $this->postJson('/api/emqx/auth', [
            'username' => 'missing-user',
            'password' => 'secret',
        ])
            ->assertOk()
            ->assertJson([
                'result' => 'deny',
                'is_superuser' => 'false',
            ]);
    }

    public function test_incorrect_passwords_are_denied(): void
    {
        IotMqttAccount::factory()->create([
            'user_name' => 'sensor-reader',
        ]);

        $this->postJson('/api/emqx/auth', [
            'username' => 'sensor-reader',
            'password' => 'wrong-password',
        ])
            ->assertOk()
            ->assertJson([
                'result' => 'deny',
                'is_superuser' => 'false',
            ]);
    }

    public function test_disabled_accounts_are_denied_even_when_the_password_is_correct(): void
    {
        // 即使密码正确，停用账号也必须 deny，避免后台停用状态在鉴权层失效。
        IotMqttAccount::factory()->create([
            'user_name' => 'disabled-user',
            ...IotMqttAccount::buildPasswordFields('public'),
            'enabled' => false,
        ]);

        $this->postJson('/api/emqx/auth', [
            'username' => 'disabled-user',
            'password' => 'public',
        ])
            ->assertOk()
            ->assertJson([
                'result' => 'deny',
                'is_superuser' => 'false',
            ]);
    }

    public function test_enabled_accounts_with_valid_passwords_are_allowed(): void
    {
        IotMqttAccount::factory()->create([
            'user_name' => 'mqtt-client',
            ...IotMqttAccount::buildPasswordFields('public'),
        ]);

        // 成功场景仍显式带 clientid，确保当前“保留字段但暂不参与限制”的契约不被误删。
        $this->postJson('/api/emqx/auth', [
            'username' => 'mqtt-client',
            'password' => 'public',
            'clientid' => 'client-001',
        ])
            ->assertOk()
            ->assertJson([
                'result' => 'allow',
                'is_superuser' => 'false',
            ]);
    }

    public function test_superuser_accounts_return_true_string_flag(): void
    {
        IotMqttAccount::factory()->create([
            'user_name' => 'mqtt-admin',
            ...IotMqttAccount::buildPasswordFields('public'),
            'is_superuser' => true,
        ]);

        // 这里锁住的是旧 EMQX 契约里的字符串布尔值，而不是 JSON 原生布尔值。
        $this->postJson('/api/emqx/auth', [
            'username' => 'mqtt-admin',
            'password' => 'public',
        ])
            ->assertOk()
            ->assertJson([
                'result' => 'allow',
                'is_superuser' => 'true',
            ]);
    }
}
