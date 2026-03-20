<?php

namespace App\Support;

use App\Models\Auth\User;

class NavigationRegistry
{
    /**
     * @return array<int, array{title: string, items: array<int, array{title: string, description: string, href: string, icon: string, permission?: string, show_in_dashboard?: bool, dashboard_description?: string}>}>
     */
    protected static function definitions(): array
    {
        return [
            [
                'title' => '工作台',
                'items' => [
                    [
                        'title' => '仪表盘',
                        'description' => '查看系统入口与首屏统计。',
                        'href' => '/dashboard',
                        'icon' => 'LayoutGrid',
                        'permission' => 'dashboard.read',
                        'show_in_dashboard' => false,
                    ],
                ],
            ],
            [
                'title' => '系统管理',
                'items' => [
                    [
                        'title' => '用户管理',
                        'description' => '维护后台用户、邮箱验证状态与基础资料。',
                        'dashboard_description' => '维护后台用户、邮箱验证状态与基础资料。',
                        'href' => '/users',
                        'icon' => 'Users',
                        'permission' => 'user.read',
                        'show_in_dashboard' => true,
                    ],
                    [
                        'title' => '角色权限',
                        'description' => '维护角色与读写权限集合。',
                        'dashboard_description' => '维护角色与模块读写权限的映射关系。',
                        'href' => '/roles',
                        'icon' => 'ShieldCheck',
                        'permission' => 'role.read',
                        'show_in_dashboard' => true,
                    ],
                    [
                        'title' => '系统设置',
                        'description' => '查看系统配置分组与后续扩展入口。',
                        'dashboard_description' => '查看配置分组与后续扩展入口。',
                        'href' => '/settings',
                        'icon' => 'Settings',
                        'permission' => 'settings.read',
                        'show_in_dashboard' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, items: array<int, array{title: string, description: string, href: string, icon: string}>}>
     */
    public static function sidebarFor(?User $user): array
    {
        return collect(self::definitions())
            ->map(function (array $section) use ($user): array {
                return [
                    'title' => $section['title'],
                    'items' => collect($section['items'])
                        ->filter(fn (array $item): bool => self::canAccess($item, $user))
                        ->map(fn (array $item): array => [
                            'title' => $item['title'],
                            'description' => $item['description'],
                            'href' => $item['href'],
                            'icon' => $item['icon'],
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $section): bool => $section['items'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{title: string, description: string, href: string}>
     */
    public static function dashboardQuickLinksFor(?User $user): array
    {
        return collect(self::definitions())
            ->flatMap(fn (array $section) => $section['items'])
            ->filter(fn (array $item): bool => ($item['show_in_dashboard'] ?? false) && self::canAccess($item, $user))
            ->map(fn (array $item): array => [
                'title' => $item['title'],
                'description' => $item['dashboard_description'] ?? $item['description'],
                'href' => $item['href'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{permission?: string}  $item
     */
    protected static function canAccess(array $item, ?User $user): bool
    {
        if (! isset($item['permission'])) {
            return true;
        }

        return $user?->can($item['permission']) === true;
    }
}
