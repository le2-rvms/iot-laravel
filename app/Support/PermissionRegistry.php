<?php

namespace App\Support;

use App\Models\User;

class PermissionRegistry
{
    public const SUPER_ADMIN_ROLE = 'Super Admin';

    /**
     * @return array<string, array{label: string, permissions: array<int, array{name: string, action: string, action_label: string}>}>
     */
    public static function groups(): array
    {
        return [
            'dashboard' => [
                'label' => '仪表盘',
                'permissions' => [
                    self::permission('dashboard.read', 'read', '读取'),
                ],
            ],
            'users' => [
                'label' => '用户管理',
                'permissions' => [
                    self::permission('users.read', 'read', '读取'),
                    self::permission('users.write', 'write', '写入'),
                ],
            ],
            'roles' => [
                'label' => '角色权限',
                'permissions' => [
                    self::permission('roles.read', 'read', '读取'),
                    self::permission('roles.write', 'write', '写入'),
                ],
            ],
            'settings' => [
                'label' => '系统设置',
                'permissions' => [
                    self::permission('settings.read', 'read', '读取'),
                ],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return collect(self::groups())
            ->flatMap(fn (array $group) => collect($group['permissions'])->pluck('name'))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{module: string, label: string, permissions: array<int, array{name: string, action: string, action_label: string}>}>
     */
    public static function groupedForFrontend(): array
    {
        return collect(self::groups())
            ->map(fn (array $group, string $module) => [
                'module' => $module,
                'label' => $group['label'],
                'permissions' => $group['permissions'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, bool>
     */
    public static function accessMap(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return collect(self::all())
            ->mapWithKeys(fn (string $permission) => [$permission => $user->can($permission)])
            ->all();
    }

    public static function superAdminRole(): string
    {
        return self::SUPER_ADMIN_ROLE;
    }

    /**
     * @return array{name: string, action: string, action_label: string}
     */
    protected static function permission(string $name, string $action, string $actionLabel): array
    {
        return [
            'name' => $name,
            'action' => $action,
            'action_label' => $actionLabel,
        ];
    }
}
