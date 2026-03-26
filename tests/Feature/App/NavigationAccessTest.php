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
                        && $links->contains(fn (array $link) => $link['title'] === '客户端监控' && $link['href'] === route('client-monitor.sessions', [], false))
                        && $links->contains(fn (array $link) => $link['title'] === '设备管理' && $link['href'] === route('devices.index', [], false))
                        && $links->contains(fn (array $link) => $link['title'] === '设备产品' && $link['href'] === route('device-products.index', [], false))
                        && $links->contains(fn (array $link) => $link['title'] === '应用配置' && $link['href'] === route('application-configs.index', [], false))
                        && $links->contains(fn (array $link) => $link['title'] === 'VeeValidate 实验室' && $link['href'] === route('vee-validate.index', [], false))
                        && $links->contains(fn (array $link) => $link['title'] === 'Precognition 实验室' && $link['href'] === route('precognition.index', [], false));
                }));
    }
}
