<?php

namespace Tests\Feature\Exports;

use App\Models\Audit;
use App\Models\Auth\AdminPermission;
use App\Models\Auth\AdminRole;
use App\Models\Auth\AdminUser;
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
            ->get('/admin/admin-users/export?search__func=alice');

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
            ->get('/admin/admin-roles/export')
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
            ->get('/admin/admin-roles/export');

        $rows = $this->csvRows($response);

        $this->assertSame(['管理员角色ID', '管理员角色名称', '权限数', '绑定管理员用户', '已选权限', '创建时间'], $rows[0]);
        $this->assertSame('Operations', $rows[1][1]);
        $this->assertSame('2', $rows[1][2]);
        $this->assertStringContainsString('仪表盘 · 读取', $rows[1][4]);
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
            ->get('/admin/mqtt-accounts/export?search__func=gateway');

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
            ->get('/admin/settings/application-configs/export?page=2');

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
            ->get('/admin/audits/export?event__eq=updated&auditable_type__eq='.urlencode(AdminUser::class));

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
        $this->assertContains('mqtt-account.read', PermissionRegistry::permissionNames());
        $this->assertContains('settings-application-config.read', PermissionRegistry::permissionNames());
        $this->assertContains('settings-system-config.read', PermissionRegistry::permissionNames());
        $this->assertContains('audit.read', PermissionRegistry::permissionNames());
    }
}
