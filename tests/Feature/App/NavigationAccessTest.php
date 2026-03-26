<?php

namespace Tests\Feature\App;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NavigationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_permission_users_receive_only_the_expected_access_map_and_quick_links(): void
    {
        $user = $this->createUserWithPermissions(['dashboard.read']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('navigation.sections', 1)
                ->where('navigation.sections.0.title', '工作台')
                ->where('navigation.sections.0.items.0.href', route('dashboard', [], false))
                ->where('navigation.sections.0.items.0.routeName', 'dashboard')
                ->where('auth.access', fn ($access) => ($access['dashboard.read'] ?? false) === true
                    && ($access['admin-user.read'] ?? false) === false
                    && ($access['admin-role.read'] ?? false) === false
                    && ($access['settings-system-config.read'] ?? false) === false
                    && ($access['settings-vee-validate.read'] ?? false) === false)
                ->where('quickLinks', []));
    }

    public function test_dashboard_exposes_settings_related_links_directly(): void
    {
        $user = $this->createUserWithPermissions([
            'dashboard.read',
            'client-monitor.read',
            'device.read',
            'device-product.read',
            'settings-application-config.read',
            'settings-vee-validate.read',
            'settings-precognition.read',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('quickLinks', function ($links): bool {
                    $links = collect($links);

                    return $links->count() === 6
                        && $links->contains(fn (array $link) => $link['title'] === '客户端监控' && $link['routeName'] === 'client-monitor.sessions')
                        && $links->contains(fn (array $link) => $link['title'] === '设备管理' && $link['routeName'] === 'devices.index')
                        && $links->contains(fn (array $link) => $link['title'] === '设备产品' && $link['routeName'] === 'device-products.index')
                        && $links->contains(fn (array $link) => $link['title'] === '应用配置' && $link['routeName'] === 'application-configs.index')
                        && $links->contains(fn (array $link) => $link['title'] === 'VeeValidate 实验室' && $link['routeName'] === 'vee-validate.index')
                        && $links->contains(fn (array $link) => $link['title'] === 'Precognition 实验室' && $link['routeName'] === 'precognition.index');
                }));
    }

    public function test_sidebar_splits_iot_and_system_management_items_into_separate_sections(): void
    {
        $user = $this->createUserWithPermissions([
            'dashboard.read',
            'device.read',
            'client-monitor.read',
            'mqtt-account.read',
            'device-product.read',
            'audit.read',
            'admin-user.read',
            'settings-system-config.read',
            'settings-vee-validate.read',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('navigation.sections.0.title', '工作台')
                ->where('navigation.sections.1.title', 'IoT')
                ->where('navigation.sections.2.title', '系统管理')
                ->where('navigation.sections.1.items', fn ($items) => collect($items)->pluck('title')->all() === [
                    '设备管理',
                    '客户端监控',
                    'MQTT账号管理',
                    '设备产品',
                ])
                ->where('navigation.sections.2.items', fn ($items) => collect($items)->pluck('title')->all() === [
                    '审计日志',
                    '管理员用户',
                    '系统配置',
                ]));
    }
}
