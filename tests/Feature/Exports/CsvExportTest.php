<?php

namespace Tests\Feature\Exports;

use App\Models\Audit;
use App\Models\Admin\AdminPermission;
use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use App\Models\Iot\IotMqttAccount;
use App\Models\Settings\Config;
use App\Support\PermissionRegistry;
use App\Values\Settings\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_export_uses_current_filters_and_downloads_csv(): void
    {
        $user = $this->createUserWithPermissions(['admin-user.read']);

        $target = AdminUser::factory()->create([
            'name' => 'Alice Export',
            'email' => 'alice-export@example.com',
            'email_verified_at' => now(),
        ]);
        $role = AdminRole::create([
            'name' => 'Exporter',
            'guard_name' => 'web',
        ]);
        $target->syncRoles([$role->name]);
        AdminUser::factory()->create([
            'name' => 'Bob Export',
            'email' => 'bob-export@example.com',
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin-users.export', ['search__func' => 'alice']));

        $response->assertOk()
            ->assertDownload();

        $rows = $this->csvRows($response);

        $this->assertSame(['管理员用户ID', '名称', '邮箱', '验证状态', '邮箱验证时间', '管理员角色', '创建时间'], $rows[0]);
        $this->assertCount(2, $rows);
        $this->assertSame('Alice Export', $rows[1][1]);
        $this->assertSame('alice-export@example.com', $rows[1][2]);
        $this->assertSame('已验证', $rows[1][3]);
        $this->assertSame('Exporter', $rows[1][5]);
    }

    public function test_admin_roles_export_requires_read_permission(): void
    {
        $user = $this->createUserWithPermissions(['dashboard.read']);

        $this->actingAs($user)
            ->get(route('admin-roles.export'))
            ->assertForbidden();
    }

    public function test_admin_roles_export_downloads_visible_role_columns(): void
    {
        $user = $this->createUserWithPermissions(['admin-role.read']);

        $role = AdminRole::create([
            'name' => 'Operations',
            'guard_name' => 'web',
        ]);
        AdminPermission::findOrCreate('dashboard.read', 'web');
        AdminPermission::findOrCreate('admin-user.read', 'web');
        $role->syncPermissions(['dashboard.read', 'admin-user.read']);

        $response = $this->actingAs($user)
            ->get(route('admin-roles.export'));

        $rows = $this->csvRows($response);

        $this->assertSame(['管理员角色ID', '管理员角色名称', '权限数', '绑定管理员用户', '已选权限', '创建时间'], $rows[0]);
        $this->assertSame('Operations', $rows[1][1]);
        $this->assertSame('2', $rows[1][2]);
        $this->assertStringContainsString('仪表盘 · 读取', $rows[1][4]);
    }

    public function test_device_products_export_uses_current_filters_and_downloads_counts(): void
    {
        \Illuminate\Support\Facades\Schema::create('device_products', function ($table): void {
            $table->increments('product_id');
            $table->string('product_key', 64)->unique();
            $table->string('product_name', 255);
            $table->text('description')->nullable();
            $table->string('manufacturer', 255)->nullable();
            $table->string('protocol', 64)->nullable();
            $table->string('category', 64)->nullable();
            $table->timestamps();
        });
        \Illuminate\Support\Facades\Schema::create('devices', function ($table): void {
            $table->string('terminal_id')->primary();
            $table->integer('dev_id')->nullable();
            $table->string('dev_name')->nullable();
            $table->string('company_id')->nullable();
            $table->string('product_key')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        \Illuminate\Support\Facades\Schema::create('device_groups', function ($table): void {
            $table->increments('group_id');
            $table->string('group_name');
            $table->string('product_key');
            $table->timestamps();
        });
        config()->set('app.company_id', 'company-1');

        $user = $this->createUserWithPermissions(['device-product.read']);

        $product = \App\Models\Iot\IotDeviceProduct::query()->create([
            'product_key' => 'PK-EXPORT',
            'product_name' => 'Export Product',
            'manufacturer' => 'RVMS',
            'protocol' => 'JT808',
            'category' => 'tracker',
        ]);
        \DB::table('devices')->insert([
            'terminal_id' => 'terminal-export',
            'dev_id' => 10,
            'dev_name' => 'Export Device',
            'company_id' => 'company-1',
            'product_key' => 'PK-EXPORT',
            'created_at' => now(),
        ]);
        \DB::table('device_groups')->insert([
            'group_name' => 'Export Group',
            'product_key' => 'PK-EXPORT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('device-products.export', ['search__func' => 'export']));

        $rows = $this->csvRows($response);

        $this->assertSame(['产品ID', '产品标识', '产品名称', '厂商', '协议', '分类', '关联设备数', '关联分组数', '创建时间'], $rows[0]);
        $this->assertSame((string) $product->product_id, $rows[1][0]);
        $this->assertSame('PK-EXPORT', $rows[1][1]);
        $this->assertSame('1', $rows[1][6]);
        $this->assertSame('1', $rows[1][7]);
    }

    public function test_devices_export_uses_current_filters_and_hides_sensitive_auth_fields(): void
    {
        \Illuminate\Support\Facades\Schema::create('device_products', function ($table): void {
            $table->increments('product_id');
            $table->string('product_key', 64)->unique();
            $table->string('product_name', 255);
            $table->timestamps();
        });
        \Illuminate\Support\Facades\Schema::create('devices', function ($table): void {
            $table->string('terminal_id', 64)->primary();
            $table->integer('dev_id')->nullable();
            $table->string('dev_name', 255);
            $table->string('company_id', 64)->nullable();
            $table->string('manufacturer_id', 64)->nullable();
            $table->string('product_key', 64)->nullable();
            $table->string('sim_number', 64)->nullable();
            $table->string('_vehicle_plate', 64)->nullable();
            $table->string('_vehicle_vin', 64)->nullable();
            $table->string('_bind_status', 64)->nullable();
            $table->string('device_status', 64)->nullable();
            $table->string('review_status', 64)->nullable();
            $table->string('auth_code_seed', 255)->nullable();
            $table->integer('city_relation_id')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        $user = $this->createUserWithPermissions(['device.read']);

        \DB::table('device_products')->insert([
            'product_key' => 'PK-DEVICE',
            'product_name' => 'Device Product',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        \DB::table('devices')->insert([
            'terminal_id' => 'terminal-export',
            'dev_id' => 20,
            'dev_name' => 'Export Device',
            'company_id' => 'company-export',
            'manufacturer_id' => 'maker-export',
            'product_key' => 'PK-DEVICE',
            'sim_number' => 'SIM-EXPORT',
            '_vehicle_plate' => '京A88888',
            '_vehicle_vin' => 'VIN-EXPORT',
            '_bind_status' => 'bound',
            'device_status' => 'online',
            'review_status' => 'approved',
            'auth_code_seed' => 'secret-seed',
            'city_relation_id' => 8,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('devices.export', ['search__func' => 'export', 'device_status__eq' => 'online']));

        $rows = $this->csvRows($response);

        $this->assertSame(['设备ID', '终端ID', '设备名称', '公司ID', '厂商ID', '产品标识', 'SIM号', '车牌号', '车架号', '绑定状态', '设备状态', '审核状态', '城市关联ID', '创建时间'], $rows[0]);
        $this->assertSame('20', $rows[1][0]);
        $this->assertSame('terminal-export', $rows[1][1]);
        $this->assertSame('online', $rows[1][10]);
        $this->assertStringNotContainsString('secret-seed', $response->streamedContent());
    }

    public function test_admin_roles_export_shows_empty_permissions_for_super_admin_role(): void
    {
        $user = $this->createSuperAdmin();

        $response = $this->actingAs($user)
            ->get(route('admin-roles.export'));

        $rows = $this->csvRows($response);

        $this->assertSame('Super Admin', $rows[1][1]);
        $this->assertSame('0', $rows[1][2]);
        $this->assertSame('', $rows[1][4]);
    }

    public function test_mqtt_accounts_export_does_not_include_sensitive_fields(): void
    {
        $user = $this->createUserWithPermissions(['mqtt-account.read']);

        $account = IotMqttAccount::factory()->create([
            'user_name' => 'gateway-export',
            'password_hash' => 'secret-hash',
            'salt' => 'secret-salt',
            'certificate' => 'secret-cert',
        ]);

        $response = $this->actingAs($user)
            ->get(route('mqtt-accounts.export', ['search__func' => 'gateway']));

        $rows = $this->csvRows($response);

        $this->assertSame(['账号ID', '账号名', '客户端标识', '产品标识', '设备名称', '超级用户', '启用状态', '更新时间', '最近更新人'], $rows[0]);
        $this->assertSame((string) $account->act_id, $rows[1][0]);
        $this->assertSame('gateway-export', $rows[1][1]);
        $this->assertStringNotContainsString('secret-hash', $response->streamedContent());
        $this->assertStringNotContainsString('secret-salt', $response->streamedContent());
        $this->assertStringNotContainsString('secret-cert', $response->streamedContent());
    }

    public function test_application_and_system_configs_export_ignore_pagination_and_keep_category_scope(): void
    {
        $user = $this->createUserWithPermissions([
            'settings-application-config.read',
            'settings-system-config.read',
        ]);

        Config::query()->create([
            'key' => 'app.name',
            'value' => 'IoT Admin',
            'category' => Category::APPLICATION,
            'is_masked' => false,
            'remark' => '应用名称',
        ]);
        Config::query()->create([
            'key' => 'app.secret',
            'value' => 'super-secret',
            'category' => Category::APPLICATION,
            'is_masked' => true,
            'remark' => '应用密钥',
        ]);
        Config::query()->create([
            'key' => 'system.timezone',
            'value' => 'Asia/Shanghai',
            'category' => Category::SYSTEM,
            'is_masked' => false,
            'remark' => '系统时区',
        ]);

        $response = $this->actingAs($user)
            ->get(route('application-configs.export', ['page' => 2]));

        $rows = $this->csvRows($response);

        $this->assertCount(3, $rows);
        $this->assertSame(['配置ID', '配置键', '配置值', '分类', '是否打码', '备注', '更新时间'], $rows[0]);
        $this->assertSame('app.name', $rows[1][1]);
        $this->assertSame('app.secret', $rows[2][1]);
        $this->assertSame('*****', $rows[2][2]);
    }

    public function test_audits_export_uses_filters_and_downloads_change_summary(): void
    {
        $user = $this->createUserWithPermissions(['audit.read']);
        $actor = AdminUser::factory()->create([
            'name' => '审计人',
            'email' => 'audit@example.com',
        ]);

        Audit::query()->create([
            'auditable_type' => AdminUser::class,
            'auditable_id' => 1,
            'actor_id' => $actor->id,
            'event' => 'updated',
            'old_values' => ['name' => '旧名'],
            'new_values' => ['name' => '新名'],
            'meta' => [
                'route' => 'admin.admin-users.update',
                'method' => 'PUT',
                'ip' => '127.0.0.1',
            ],
        ]);
        Audit::query()->create([
            'auditable_type' => Config::class,
            'auditable_id' => 2,
            'actor_id' => null,
            'event' => 'created',
            'old_values' => null,
            'new_values' => ['key' => 'app.name'],
            'meta' => [
                'route' => 'admin.settings.application-configs.store',
                'method' => 'POST',
                'ip' => '127.0.0.1',
            ],
        ]);

        $response = $this->actingAs($user)
            ->get(route('audits.export', [
                'event__eq' => 'updated',
                'auditable_type__eq' => AdminUser::class,
            ]));

        $rows = $this->csvRows($response);

        $this->assertSame(['审计ID', '时间', '事件', '资源', '资源ID', '操作者', '操作者邮箱', '路由', '方法', 'IP', '变更内容'], $rows[0]);
        $this->assertCount(2, $rows);
        $this->assertSame('更新', $rows[1][2]);
        $this->assertSame('管理员用户', $rows[1][3]);
        $this->assertSame('审计人', $rows[1][5]);
        $this->assertStringContainsString('"名称":"旧名 → 新名"', $rows[1][10]);
    }

    public function test_permission_registry_still_contains_read_permissions_for_exported_modules(): void
    {
        $this->assertContains('admin-user.read', PermissionRegistry::permissionNames());
        $this->assertContains('admin-role.read', PermissionRegistry::permissionNames());
        $this->assertContains('client-monitor.read', PermissionRegistry::permissionNames());
        $this->assertContains('device-product.read', PermissionRegistry::permissionNames());
        $this->assertContains('mqtt-account.read', PermissionRegistry::permissionNames());
        $this->assertContains('settings-application-config.read', PermissionRegistry::permissionNames());
        $this->assertContains('settings-system-config.read', PermissionRegistry::permissionNames());
        $this->assertContains('audit.read', PermissionRegistry::permissionNames());
    }
}
