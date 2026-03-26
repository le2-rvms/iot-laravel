<?php

namespace App\Support;

use App\Models\Admin\AdminUser;

class NavigationRegistry
{
    /**
     * @return array<int, array{title: string, items: array<int, array{title: string, description: string, route: string, icon: string, permission?: string, show_in_sidebar?: bool, show_in_dashboard?: bool, dashboard_description?: string}>}>
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
                        'route' => 'dashboard',
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
                        'title' => '管理员用户',
                        'description' => '维护后台管理员用户、邮箱验证状态与基础资料。',
                        'dashboard_description' => '维护后台管理员用户、邮箱验证状态与基础资料。',
                        'route' => 'admin-users.index',
                        'icon' => 'Users',
                        'permission' => 'admin-user.read',
                        'show_in_dashboard' => true,
                    ],
                    [
                        'title' => '管理员角色',
                        'description' => '维护管理员角色与读写权限集合。',
                        'dashboard_description' => '维护管理员角色与模块读写权限的映射关系。',
                        'route' => 'admin-roles.index',
                        'icon' => 'ShieldCheck',
                        'permission' => 'admin-role.read',
                        'show_in_dashboard' => true,
                    ],
                    [
                        'title' => '审计日志',
                        'description' => '查看后台资源的创建、更新、删除与业务事件记录。',
                        'dashboard_description' => '查看后台资源的创建、更新、删除与业务事件记录。',
                        'route' => 'audits.index',
                        'icon' => 'History',
                        'permission' => 'audit.read',
                        'show_in_dashboard' => true,
                    ],
                    [
                        'title' => 'MQTT账号管理',
                        'description' => '维护 MQTT 连接账号、设备标识与启用状态。',
                        'dashboard_description' => '维护 MQTT 连接账号、设备标识与启用状态。',
                        'route' => 'mqtt-accounts.index',
                        'icon' => 'Waypoints',
                        'permission' => 'mqtt-account.read',
                        'show_in_dashboard' => true,
                    ],
                    [
                        'title' => '客户端监控',
                        'description' => '查看客户端在线会话、鉴权结果、命令事件和连接事件。',
                        'dashboard_description' => '查看客户端在线会话、鉴权结果、命令事件和连接事件。',
                        'route' => 'client-monitor.sessions',
                        'show_in_dashboard' => true,
                        'icon' => 'ScanSearch',
                        'permission' => 'client-monitor.read',
                    ],
                    [
                        'title' => '设备管理',
                        'description' => '维护设备标识、车辆信息、状态字段与鉴权信息。',
                        'dashboard_description' => '维护设备标识、车辆信息、状态字段与鉴权信息。',
                        'route' => 'devices.index',
                        'icon' => 'Cpu',
                        'permission' => 'device.read',
                        'show_in_dashboard' => true,
                    ],
                    [
                        'title' => '设备产品',
                        'description' => '维护设备产品标识、名称与协议分类信息。',
                        'dashboard_description' => '维护设备产品标识、名称与协议分类信息。',
                        'route' => 'device-products.index',
                        'icon' => 'Package',
                        'permission' => 'device-product.read',
                        'show_in_dashboard' => true,
                    ],
                    [
                        'title' => '应用配置',
                        'description' => '维护应用层的可配置键值、打码策略与备注说明。',
                        'dashboard_description' => '维护应用层的可配置键值、打码策略与备注说明。',
                        'route' => 'application-configs.index',
                        'icon' => 'SlidersHorizontal',
                        'permission' => 'settings-application-config.read',
                        'show_in_dashboard' => true,
                    ],
                    [
                        'title' => '系统配置',
                        'description' => '维护系统层的公共设定、展示策略与后台说明。',
                        'dashboard_description' => '维护系统层的公共设定、展示策略与后台说明。',
                        'route' => 'system-configs.index',
                        'icon' => 'SlidersVertical',
                        'permission' => 'settings-system-config.read',
                        'show_in_dashboard' => true,
                    ],
                    [
                        'title' => 'VeeValidate 实验室',
                        'description' => '用于演练通知规则的填写流程。',
                        'dashboard_description' => '用于演练通知规则的填写流程。',
                        'route' => 'vee-validate.index',
                        'icon' => 'FileCheck2',
                        'permission' => 'settings-vee-validate.read',
                        'show_in_sidebar' => false,
                        'show_in_dashboard' => true,
                    ],
                    [
                        'title' => 'Precognition 实验室',
                        'description' => '用于体验填写过程中的实时校验反馈。',
                        'dashboard_description' => '用于体验填写过程中的实时校验反馈。',
                        'route' => 'precognition.index',
                        'icon' => 'ScanSearch',
                        'permission' => 'settings-precognition.read',
                        'show_in_sidebar' => false,
                        'show_in_dashboard' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, items: array<int, array{title: string, description: string, href: string, routeName: string, icon: string}>}>
     */
    public static function sidebarFor(?AdminUser $user): array
    {
        return collect(self::definitions())
            ->map(function (array $section) use ($user): array {
                return [
                    'title' => $section['title'],
                    'items' => collect($section['items'])
                        ->filter(fn (array $item): bool => ($item['show_in_sidebar'] ?? true) && self::canAccess($item, $user))
                        ->map(fn (array $item): array => [
                            'title' => $item['title'],
                            'description' => $item['description'],
                            'href' => self::hrefFor($item),
                            'routeName' => $item['route'],
                            'icon' => $item['icon'],
                        ])
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->filter(fn (array $section): bool => $section['items'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{title: string, description: string, href: string, routeName: string}>
     */
    public static function dashboardQuickLinksFor(?AdminUser $user): array
    {
        return collect(self::definitions())
            ->flatMap(fn (array $section) => $section['items'])
            ->filter(fn (array $item): bool => ($item['show_in_dashboard'] ?? false) && self::canAccess($item, $user))
            ->map(fn (array $item): array => [
                'title' => $item['title'],
                'description' => $item['dashboard_description'] ?? $item['description'],
                'href' => self::hrefFor($item),
                'routeName' => $item['route'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{permission?: string}  $item
     */
    protected static function canAccess(array $item, ?AdminUser $user): bool
    {
        if (! isset($item['permission'])) {
            return true;
        }

        return $user?->can($item['permission']) === true;
    }

    /**
     * @param  array{route: string}  $item
     */
    protected static function hrefFor(array $item): string
    {
        return route($item['route'], [], false);
    }
}
