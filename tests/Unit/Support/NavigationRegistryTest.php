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
        $this->assertSame(route('dashboard', [], false), $sections[0]['items'][0]['href']);
        $this->assertSame('系统管理', $sections[1]['title']);
        $this->assertSame(
            [
                route('admin-users.index', [], false),
                route('admin-roles.index', [], false),
                route('audits.index', [], false),
                route('mqtt-accounts.index', [], false),
                route('client-monitor.sessions', [], false),
                route('devices.index', [], false),
                route('device-products.index', [], false),
                route('application-configs.index', [], false),
                route('system-configs.index', [], false),
            ],
            array_column($systemManagement, 'href'),
        );
        $this->assertSame(route('client-monitor.sessions', [], false), $clientMonitor['href']);
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
                        'href' => route('dashboard', [], false),
                        'routeName' => 'dashboard',
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
                'href' => route('admin-users.index', [], false),
                'routeName' => 'admin-users.index',
            ],
            [
                'title' => 'MQTT账号管理',
                'description' => '维护 MQTT 连接账号、设备标识与启用状态。',
                'href' => route('mqtt-accounts.index', [], false),
                'routeName' => 'mqtt-accounts.index',
            ],
            [
                'title' => '客户端监控',
                'description' => '查看客户端在线会话、鉴权结果、命令事件和连接事件。',
                'href' => route('client-monitor.sessions', [], false),
                'routeName' => 'client-monitor.sessions',
            ],
            [
                'title' => '设备管理',
                'description' => '维护设备标识、车辆信息、状态字段与鉴权信息。',
                'href' => route('devices.index', [], false),
                'routeName' => 'devices.index',
            ],
            [
                'title' => '设备产品',
                'description' => '维护设备产品标识、名称与协议分类信息。',
                'href' => route('device-products.index', [], false),
                'routeName' => 'device-products.index',
            ],
            [
                'title' => '系统配置',
                'description' => '维护系统层的公共设定、展示策略与后台说明。',
                'href' => route('system-configs.index', [], false),
                'routeName' => 'system-configs.index',
            ],
            [
                'title' => 'VeeValidate 实验室',
                'description' => '用于演练通知规则的填写流程。',
                'href' => route('vee-validate.index', [], false),
                'routeName' => 'vee-validate.index',
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
        $this->assertSame(route('audits.index', [], false), $systemManagement['items'][0]['href']);
        $this->assertSame('审计日志', $systemManagement['items'][0]['title']);
        $this->assertSame([
            [
                'title' => '审计日志',
                'description' => '查看后台资源的创建、更新、删除与业务事件记录。',
                'href' => route('audits.index', [], false),
                'routeName' => 'audits.index',
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
        $this->assertSame(route('client-monitor.sessions', [], false), $clientMonitor['href']);
    }
}
