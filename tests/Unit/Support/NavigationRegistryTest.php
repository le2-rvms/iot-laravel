<?php

namespace Tests\Unit\Support;

use App\Support\NavigationRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_receives_the_full_sidebar_navigation(): void
    {
        $user = $this->createSuperAdmin();

        $sections = NavigationRegistry::sidebarFor($user);
        $systemManagement = $sections[1]['items'];
        $clientMonitor = collect($systemManagement)->firstWhere('title', '客户端监控');

        $this->assertCount(2, $sections);
        $this->assertSame('工作台', $sections[0]['title']);
        $this->assertSame('/admin/dashboard', $sections[0]['items'][0]['href']);
        $this->assertSame('系统管理', $sections[1]['title']);
        $this->assertSame(
            ['/admin/admin-users', '/admin/admin-roles', '/admin/audits', '/admin/mqtt-accounts', '/admin/client-monitor/sessions', '/admin/devices', '/admin/device-products', '/admin/settings/application-configs', '/admin/settings/system-configs'],
            array_column($systemManagement, 'href'),
        );
        $this->assertSame('/admin/client-monitor/sessions', $clientMonitor['href']);
    }

    public function test_dashboard_only_users_receive_only_dashboard_sidebar_item(): void
    {
        $user = $this->createUserWithPermissions(['dashboard.read']);

        $sections = NavigationRegistry::sidebarFor($user);

        $this->assertSame([
            [
                'title' => '工作台',
                'items' => [
                    [
                        'title' => '仪表盘',
                        'description' => '查看系统入口与首屏统计。',
                        'href' => '/admin/dashboard',
                        'icon' => 'LayoutGrid',
                    ],
                ],
            ],
        ], $sections);
    }

    public function test_guests_do_not_receive_protected_sidebar_items(): void
    {
        $this->assertSame([], NavigationRegistry::sidebarFor(null));
    }

    public function test_dashboard_quick_links_only_include_allowed_items_marked_for_dashboard(): void
    {
        $user = $this->createUserWithPermissions(['admin-user.read', 'mqtt-account.read', 'client-monitor.read', 'device.read', 'device-product.read', 'settings-system-config.read', 'settings-vee-validate.read']);

        $links = NavigationRegistry::dashboardQuickLinksFor($user);

        $this->assertSame([
            [
                'title' => '管理员用户',
                'description' => '维护后台管理员用户、邮箱验证状态与基础资料。',
                'href' => '/admin/admin-users',
            ],
            [
                'title' => 'MQTT账号管理',
                'description' => '维护 MQTT 连接账号、设备标识与启用状态。',
                'href' => '/admin/mqtt-accounts',
            ],
            [
                'title' => '设备管理',
                'description' => '维护设备标识、车辆信息、状态字段与鉴权信息。',
                'href' => '/admin/devices',
            ],
            [
                'title' => '设备产品',
                'description' => '维护设备产品标识、名称与协议分类信息。',
                'href' => '/admin/device-products',
            ],
            [
                'title' => '系统配置',
                'description' => '维护系统层的公共设定、展示策略与后台说明。',
                'href' => '/admin/settings/system-configs',
            ],
            [
                'title' => 'VeeValidate 实验室',
                'description' => '用于演练通知规则的填写流程。',
                'href' => '/admin/settings/vee-validate',
            ],
        ], $links);
    }

    public function test_audit_navigation_is_visible_to_users_with_audit_read_permission(): void
    {
        $user = $this->createUserWithPermissions(['audit.read']);

        $sections = NavigationRegistry::sidebarFor($user);
        $links = NavigationRegistry::dashboardQuickLinksFor($user);
        $systemManagement = collect($sections)->firstWhere('title', '系统管理');

        $this->assertNotNull($systemManagement);
        $this->assertSame('/admin/audits', $systemManagement['items'][0]['href']);
        $this->assertSame('审计日志', $systemManagement['items'][0]['title']);
        $this->assertSame([
            [
                'title' => '审计日志',
                'description' => '查看后台资源的创建、更新、删除与业务事件记录。',
                'href' => '/admin/audits',
            ],
        ], $links);
    }

    public function test_client_monitor_sidebar_item_is_a_single_link_without_second_level_children(): void
    {
        $user = $this->createUserWithPermissions(['client-monitor.read']);

        $sections = NavigationRegistry::sidebarFor($user);
        $systemManagement = collect($sections)->firstWhere('title', '系统管理');
        $clientMonitor = collect($systemManagement['items'])->firstWhere('title', '客户端监控');

        $this->assertNotNull($clientMonitor);
        $this->assertSame('/admin/client-monitor/sessions', $clientMonitor['href']);
    }
}
