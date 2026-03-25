<?php

namespace Tests\Unit\Models\Iot;

use App\Models\Iot\IotMqttAccount;
use App\Values\Iot\Enabled;
use App\Values\Iot\IsSuperuser;
use Tests\TestCase;

class MqttAccountTest extends TestCase
{
    public function test_build_password_fields_generates_a_salt_and_password_hash(): void
    {
        $fields = IotMqttAccount::buildPasswordFields('public');

        $this->assertArrayHasKey('salt', $fields);
        $this->assertArrayHasKey('password_hash', $fields);
        $this->assertSame(10, strlen($fields['salt']));
        $this->assertSame(
            IotMqttAccount::makePasswordHash('public', $fields['salt']),
            $fields['password_hash'],
        );
    }

    public function test_check_password_verifies_the_plain_text_password(): void
    {
        $fields = IotMqttAccount::buildPasswordFields('public');
        $account = new IotMqttAccount($fields);

        $this->assertTrue($account->checkPassword('public'));
        $this->assertFalse($account->checkPassword('private'));
    }

    public function test_enum_like_casts_are_resolved_for_superuser_and_enabled_fields(): void
    {
        $account = new IotMqttAccount([
            'is_superuser' => 1,
            'enabled' => 0,
        ]);

        $this->assertInstanceOf(IsSuperuser::class, $account->is_superuser);
        $this->assertInstanceOf(Enabled::class, $account->enabled);
        $this->assertTrue($account->is_superuser->isEnabled());
        $this->assertFalse($account->enabled->isEnabled());
        $this->assertSame('是', $account->is_superuser->label);
        $this->assertSame('停用', $account->enabled->label);
    }
}
