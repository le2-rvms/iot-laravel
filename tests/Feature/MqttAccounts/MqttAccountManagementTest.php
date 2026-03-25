<?php

namespace Tests\Feature\MqttAccounts;

use App\Models\Audit;
use App\Models\Iot\IotMqttAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MqttAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_read_permission_can_view_the_mqtt_accounts_index(): void
    {
        $user = $this->createUserWithPermissions(['mqtt-account.read']);

        IotMqttAccount::factory()->count(3)->create();

        $this->actingAs($user)
            ->get('/admin/mqtt-accounts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MqttAccount/Index')
                ->has('accounts.data', 3)
                ->where('filters', [])
                ->where('auth.access', fn ($access) => ($access['mqtt-account.read'] ?? false) === true));
    }

    public function test_users_without_read_permission_cannot_view_the_mqtt_accounts_index(): void
    {
        $user = $this->createUserWithPermissions(['dashboard.read']);

        $this->actingAs($user)
            ->get('/admin/mqtt-accounts')
            ->assertForbidden();
    }

    public function test_users_with_write_permission_can_create_update_and_delete_mqtt_accounts(): void
    {
        $user = $this->createUserWithPermissions(['mqtt-account.write']);

        $this->actingAs($user)
            ->post('/admin/mqtt-accounts', [
                'user_name' => 'device-gateway',
                'password' => 'secret-pass',
                'clientid' => 'client-001',
                'product_key' => 'pk-001',
                'device_name' => 'gateway-1',
                'certificate' => 'cert-data',
                'is_superuser' => false,
                'enabled' => true,
            ])
            ->assertRedirect('/admin/mqtt-accounts')
            ->assertSessionHas('success', 'MQTT账号已创建。');

        $account = IotMqttAccount::query()->where('user_name', 'device-gateway')->firstOrFail();
        $originalHash = $account->password_hash;

        $this->assertSame('client-001', $account->clientid);
        $this->assertTrue($account->checkPassword('secret-pass'));
        $this->assertSame($user->email, $account->act_updated_by);

        $this->actingAs($user)
            ->put("/admin/mqtt-accounts/{$account->act_id}", [
                'user_name' => 'device-gateway',
                'password' => '',
                'clientid' => 'client-002',
                'product_key' => 'pk-002',
                'device_name' => 'gateway-2',
                'certificate' => 'cert-data-updated',
                'is_superuser' => true,
                'enabled' => true,
            ])
            ->assertRedirect("/admin/mqtt-accounts/{$account->act_id}/edit")
            ->assertSessionHas('success', 'MQTT账号已更新。');

        $account->refresh();

        $this->assertSame('client-002', $account->clientid);
        $this->assertSame($originalHash, $account->password_hash);
        $this->assertTrue($account->checkPassword('secret-pass'));
        $this->assertTrue($account->is_superuser->isEnabled());

        $this->actingAs($user)
            ->put("/admin/mqtt-accounts/{$account->act_id}", [
                'user_name' => 'device-gateway',
                'password' => 'new-secret-pass',
                'clientid' => 'client-002',
                'product_key' => 'pk-002',
                'device_name' => 'gateway-2',
                'certificate' => 'cert-data-updated',
                'is_superuser' => true,
                'enabled' => false,
            ])
            ->assertRedirect("/admin/mqtt-accounts/{$account->act_id}/edit");

        $account->refresh();

        $this->assertNotSame($originalHash, $account->password_hash);
        $this->assertTrue($account->checkPassword('new-secret-pass'));
        $this->assertFalse($account->enabled->isEnabled());

        $this->actingAs($user)
            ->delete("/admin/mqtt-accounts/{$account->act_id}")
            ->assertRedirect('/admin/mqtt-accounts')
            ->assertSessionHas('success', 'MQTT账号已删除。');

        $this->assertDatabaseMissing('mqtt_accounts', [
            'act_id' => $account->act_id,
        ]);
    }

    public function test_updating_mqtt_account_password_writes_a_password_changed_audit_marker(): void
    {
        $user = $this->createUserWithPermissions(['mqtt-account.write']);

        $account = IotMqttAccount::factory()->create([
            'user_name' => 'device-gateway',
            'clientid' => 'client-001',
            'is_superuser' => false,
            'enabled' => true,
        ]);

        Audit::query()->delete();

        $this->actingAs($user)
            ->put("/admin/mqtt-accounts/{$account->act_id}", [
                'user_name' => 'device-gateway',
                'password' => 'new-secret-pass',
                'clientid' => 'client-001',
                'product_key' => $account->product_key,
                'device_name' => $account->device_name,
                'certificate' => $account->certificate,
                'is_superuser' => true,
                'enabled' => true,
            ])
            ->assertRedirect("/admin/mqtt-accounts/{$account->act_id}/edit");

        $audit = Audit::query()->latest('id')->firstOrFail();

        $this->assertSame('updated', $audit->event);
        $this->assertSame('[已隐藏]', $audit->old_values['password_hash']);
        $this->assertSame('[已隐藏]', $audit->new_values['password_hash']);
        $this->assertSame('[已隐藏]', $audit->old_values['salt']);
        $this->assertSame('[已隐藏]', $audit->new_values['salt']);
        $this->assertSame(0, $audit->old_values['is_superuser']);
        $this->assertSame(1, $audit->new_values['is_superuser']);
        $this->assertContains('password', $audit->changed_fields);
        $this->assertStringContainsString('"密码":"[已修改]"', $audit->change_summary);
        $this->assertStringContainsString('"是否超级用户":"否 → 是"', $audit->change_summary);
    }

    public function test_updating_an_account_refreshes_the_updated_by_user(): void
    {
        $creator = $this->createUserWithPermissions(['mqtt-account.write']);
        $editor = $this->createUserWithPermissions(['mqtt-account.write']);

        $this->actingAs($creator)
            ->post('/admin/mqtt-accounts', [
                'user_name' => 'device-gateway',
                'password' => 'secret-pass',
                'clientid' => 'client-001',
                'product_key' => 'pk-001',
                'device_name' => 'gateway-1',
                'certificate' => 'cert-data',
                'is_superuser' => false,
                'enabled' => true,
            ])
            ->assertRedirect('/admin/mqtt-accounts');

        $account = IotMqttAccount::query()->where('user_name', 'device-gateway')->firstOrFail();

        $this->assertSame($creator->email, $account->act_updated_by);

        $this->actingAs($editor)
            ->put("/admin/mqtt-accounts/{$account->act_id}", [
                'user_name' => 'device-gateway',
                'password' => '',
                'clientid' => 'client-002',
                'product_key' => 'pk-002',
                'device_name' => 'gateway-2',
                'certificate' => 'cert-data-updated',
                'is_superuser' => true,
                'enabled' => true,
            ])
            ->assertRedirect("/admin/mqtt-accounts/{$account->act_id}/edit");

        $account->refresh();

        // 这里锁的是“最近更新人”语义，避免字段退化成首次写入人。
        $this->assertSame($editor->email, $account->act_updated_by);
    }

    public function test_explicit_updated_by_values_are_not_overwritten_by_the_trait(): void
    {
        $user = $this->createUserWithPermissions(['mqtt-account.write']);
        $account = IotMqttAccount::factory()->create([
            'act_updated_by' => 'creator@example.com',
        ]);

        $this->actingAs($user);

        $account->fill([
            'clientid' => 'import-client',
            'act_updated_by' => 'import-job@example.com',
        ]);
        $account->save();

        $account->refresh();

        $this->assertSame('import-job@example.com', $account->act_updated_by);
    }

    public function test_save_preserving_updated_by_keeps_explicit_same_value_assignments(): void
    {
        $user = $this->createUserWithPermissions(['mqtt-account.write']);
        $account = IotMqttAccount::factory()->create([
            'act_updated_by' => 'creator@example.com',
        ]);

        $this->actingAs($user);

        $account->fill([
            'clientid' => 'import-client',
            'act_updated_by' => 'creator@example.com',
        ]);
        // 这里锁的是“显式赋值但值与原值相同”时，仍然可以保留调用方意图。
        $account->savePreservingUpdatedBy();

        $account->refresh();

        $this->assertSame('creator@example.com', $account->act_updated_by);
    }

    public function test_save_preserving_updated_by_only_applies_to_the_current_save(): void
    {
        $editor = $this->createUserWithPermissions(['mqtt-account.write']);
        $account = IotMqttAccount::factory()->create([
            'act_updated_by' => 'creator@example.com',
        ]);

        $this->actingAs($editor);

        $account->fill([
            'clientid' => 'import-client',
            'act_updated_by' => 'creator@example.com',
        ]);
        $account->savePreservingUpdatedBy();

        $account->refresh();
        $this->assertSame('creator@example.com', $account->act_updated_by);

        $account->fill([
            'clientid' => 'import-client-updated',
        ]);
        // 第二次普通保存应恢复自动回填，防止 preserve 状态泄漏到后续操作。
        $account->save();

        $account->refresh();
        $this->assertSame($editor->email, $account->act_updated_by);
    }

    public function test_saving_without_business_changes_does_not_refresh_updated_by(): void
    {
        $user = $this->createUserWithPermissions(['mqtt-account.write']);
        $account = IotMqttAccount::factory()->create([
            'act_updated_by' => 'creator@example.com',
        ]);

        $this->actingAs($user);

        $account->save();
        $account->refresh();

        $this->assertSame('creator@example.com', $account->act_updated_by);
    }

    public function test_mqtt_accounts_can_be_filtered_by_search(): void
    {
        $user = $this->createUserWithPermissions(['mqtt-account.read']);

        IotMqttAccount::factory()->create([
            'user_name' => 'alpha-gateway',
            'clientid' => 'client-alpha',
            'product_key' => 'pk-alpha',
            'device_name' => 'alpha-device',
        ]);

        IotMqttAccount::factory()->create([
            'user_name' => 'beta-gateway',
            'clientid' => 'client-beta',
            'product_key' => 'pk-beta',
            'device_name' => 'beta-device',
        ]);

        $this->actingAs($user)
            ->get('/admin/mqtt-accounts?search__func=alpha')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MqttAccount/Index')
                ->where('filters.search__func', 'alpha')
                ->has('accounts.data', 1)
                ->where('accounts.data.0.user_name', 'alpha-gateway'));
    }

    public function test_mqtt_accounts_search_is_case_insensitive(): void
    {
        $user = $this->createUserWithPermissions(['mqtt-account.read']);

        IotMqttAccount::factory()->create([
            'user_name' => 'Gateway-A',
            'clientid' => 'Client-Gateway-A',
            'product_key' => 'PK-Gateway-A',
            'device_name' => 'Device-Gateway-A',
        ]);

        IotMqttAccount::factory()->create([
            'user_name' => 'Sensor-B',
            'clientid' => 'Client-Sensor-B',
            'product_key' => 'PK-Sensor-B',
            'device_name' => 'Device-Sensor-B',
        ]);

        $this->actingAs($user)
            ->get('/admin/mqtt-accounts?search__func=gateway')
            ->assertOk()
            // 这里锁的是 MQTT 列表与配置列表一致的大小写不敏感搜索约定。
            ->assertInertia(fn (Assert $page) => $page
                ->component('MqttAccount/Index')
                ->where('filters.search__func', 'gateway')
                ->has('accounts.data', 1)
                ->where('accounts.data.0.user_name', 'Gateway-A'));
    }

    public function test_mqtt_accounts_can_be_filtered_by_declared_boolean_field(): void
    {
        $user = $this->createUserWithPermissions(['mqtt-account.read']);

        IotMqttAccount::factory()->create([
            'user_name' => 'enabled-account',
            'enabled' => true,
        ]);
        IotMqttAccount::factory()->create([
            'user_name' => 'disabled-account',
            'enabled' => false,
        ]);

        $this->actingAs($user)
            ->get('/admin/mqtt-accounts?enabled__eq=0')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MqttAccount/Index')
                ->where('filters.enabled__eq', '0')
                ->has('accounts.data', 1)
                ->where('accounts.data.0.user_name', 'disabled-account'));
    }

    public function test_read_only_users_cannot_open_create_or_delete_mqtt_accounts(): void
    {
        $user = $this->createUserWithPermissions(['mqtt-account.read']);
        $account = IotMqttAccount::factory()->create();

        $this->actingAs($user)
            ->get('/admin/mqtt-accounts')
            ->assertOk();

        $this->actingAs($user)
            ->get('/admin/mqtt-accounts/create')
            ->assertForbidden();

        $this->actingAs($user)
            ->delete("/admin/mqtt-accounts/{$account->act_id}")
            ->assertForbidden();
    }

    public function test_validation_errors_are_returned_in_chinese(): void
    {
        $user = $this->createUserWithPermissions(['mqtt-account.write']);

        $this->actingAs($user)
            ->from('/admin/mqtt-accounts/create')
            ->post('/admin/mqtt-accounts', [
                'user_name' => '',
                'password' => '',
                'clientid' => str_repeat('x', 51),
                'product_key' => str_repeat('y', 65),
                'device_name' => str_repeat('z', 256),
                'certificate' => '',
                'is_superuser' => '',
                'enabled' => '',
            ])
            ->assertRedirect('/admin/mqtt-accounts/create')
            ->assertSessionHasErrors(['user_name', 'password', 'clientid', 'product_key', 'device_name', 'is_superuser', 'enabled']);

        $errors = session('errors')->getBag('default');

        $this->assertSame('账号名 不能为空。', $errors->first('user_name'));
        $this->assertSame('密码 不能为空。', $errors->first('password'));
        $this->assertSame('客户端标识 不能大于 50 个字符。', $errors->first('clientid'));
        $this->assertSame('产品标识 不能大于 64 个字符。', $errors->first('product_key'));
        $this->assertSame('设备名称 不能大于 255 个字符。', $errors->first('device_name'));
        $this->assertSame('是否超级用户 不能为空。', $errors->first('is_superuser'));
        $this->assertSame('启用状态 不能为空。', $errors->first('enabled'));
    }
}
