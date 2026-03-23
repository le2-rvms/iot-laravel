<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;
use InvalidArgumentException;

class PermissionLocalizer
{
    /**
     * @param  array<int, array{
     *     module: string,
     *     permissions: array<int, array{name: string, action: string}>
     * }>  $definitions
     * @return array<int, array{
     *     module: string,
     *     label: string,
     *     permissions: array<int, array{name: string, action: string, action_label: string}>
     * }>
     */
    public function groups(array $definitions): array
    {
        // 分组结果是给 UI 表单消费的，存储层仍然只认权限名。
        return array_map(
            fn (array $definition): array => $this->group($definition),
            $definitions,
        );
    }

    /**
     * @param  array<int, string>  $permissionNames
     * @return array<string, string>
     */
    public function displayNames(array $permissionNames): array
    {
        $labels = [];

        // 角色列表和表格更适合消费平铺映射，而不是分组结构。
        foreach ($permissionNames as $permissionName) {
            $labels[$permissionName] = $this->displayName($permissionName);
        }

        return $labels;
    }

    public function displayName(string $permissionName): string
    {
        [$module, $action] = $this->parsePermissionName($permissionName);

        // 存储里只保留稳定权限名，展示文案统一从语言包推导。
        return "{$this->groupLabel($module)} · {$this->actionLabel($action)}";
    }

    /**
     * @param  array{
     *     module: string,
     *     permissions: array<int, array{name: string, action: string}>
     * }  $definition
     * @return array{
     *     module: string,
     *     label: string,
     *     permissions: array<int, array{name: string, action: string, action_label: string}>
     * }
     */
    private function group(array $definition): array
    {
        return [
            'module' => $definition['module'],
            'label' => $this->groupLabel($definition['module']),
            'permissions' => array_map(
                // 权限名和动作本身已经是规范值，这里只给 UI 追加动作文案。
                fn (array $permission): array => [
                    ...$permission,
                    'action_label' => $this->actionLabel($permission['action']),
                ],
                $definition['permissions'],
            ),
        ];
    }

    private function groupLabel(string $module): string
    {
        // 模块文案来自语言包，而不是权限表里的持久化元数据。
        return Lang::get("controllers.groups.{$module}");
    }

    private function actionLabel(string $action): string
    {
        // 动作文案在所有模块间复用。
        return Lang::get("controllers.actions.{$action}");
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parsePermissionName(string $permissionName): array
    {
        $segments = explode('.', $permissionName, 2);

        // 所有权限标识都要求满足 “{module}.{action}” 结构。
        if (count($segments) !== 2 || $segments[0] === '' || $segments[1] === '') {
            throw new InvalidArgumentException("Invalid permission name [{$permissionName}].");
        }

        return [$segments[0], $segments[1]];
    }
}
