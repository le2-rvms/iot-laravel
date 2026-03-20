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

        $this->assertCount(2, $sections);
        $this->assertSame('工作台', $sections[0]['title']);
        $this->assertSame('/dashboard', $sections[0]['items'][0]['href']);
        $this->assertSame('系统管理', $sections[1]['title']);
        $this->assertSame(
            ['/users', '/roles', '/settings'],
            array_column($sections[1]['items'], 'href'),
        );
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
                        'href' => '/dashboard',
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
        $user = $this->createUserWithPermissions(['user.read', 'settings.read']);

        $links = NavigationRegistry::dashboardQuickLinksFor($user);

        $this->assertSame([
            [
                'title' => '用户管理',
                'description' => '维护后台用户、邮箱验证状态与基础资料。',
                'href' => '/users',
            ],
            [
                'title' => '系统设置',
                'description' => '查看配置分组与后续扩展入口。',
                'href' => '/settings',
            ],
        ], $links);
    }
}
