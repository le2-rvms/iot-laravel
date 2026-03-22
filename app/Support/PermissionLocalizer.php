<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

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
    public function definitions(array $definitions): array
    {
        return array_map(
            fn (array $definition): array => $this->localizeDefinition($definition),
            $definitions,
        );
    }

    /**
     * @param  array<int, array{
     *     module: string,
     *     permissions: array<int, array{name: string, action: string}>
     * }>  $definitions
     * @return array<string, string>
     */
    public function permissionLabels(array $definitions): array
    {
        $labels = [];

        foreach ($definitions as $definition) {
            $groupLabel = Lang::get("permissions.groups.{$definition['module']}");

            foreach ($definition['permissions'] as $permission) {
                $labels[$permission['name']] = "{$groupLabel} · ".Lang::get("permissions.actions.{$permission['action']}");
            }
        }

        return $labels;
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
    private function localizeDefinition(array $definition): array
    {
        return [
            'module' => $definition['module'],
            'label' => Lang::get("permissions.groups.{$definition['module']}"),
            'permissions' => array_map(
                static fn (array $permission): array => [
                    ...$permission,
                    'action_label' => Lang::get("permissions.actions.{$permission['action']}"),
                ],
                $definition['permissions'],
            ),
        ];
    }
}
