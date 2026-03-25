<?php

namespace App\Support;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Middleware\AuthorizeControllerPermission;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LogicException;
use ReflectionClass;
use ReflectionMethod;

class PermissionStructureBuilder
{
    /**
     * @var array<string, int>
     */
    private const ACTION_PRIORITIES = [
        'read' => 0,
        'write' => 1,
    ];

    /**
     * 在构建阶段扫描控制器 Attribute，生成运行时只读的权限清单。
     *
     * @return array{
     *     definitions: array<int, array{
     *         module: string,
     *         permissions: array<int, array{name: string, action: string}>
     *     }>,
     * }
     */
    public function build(): array
    {
        $protectedControllerActions = $this->protectedControllerActions();
        $definitions = [];
        $actions = [];

        // 同时从文件系统和受保护路由做发现，保证测试里的临时控制器也能参与。
        foreach (array_unique([
            ...array_map(
                static fn ($file): string => 'App\\'.str_replace(
                    [DIRECTORY_SEPARATOR, '.php'],
                    ['\\', ''],
                    Str::after($file->getRealPath(), app_path().DIRECTORY_SEPARATOR),
                ),
                File::allFiles(app_path('Http/Controllers')),
            ),
            ...array_column($protectedControllerActions, 0),
        ]) as $controllerClass) {
            if (! class_exists($controllerClass)) {
                continue;
            }

            $reflection = new ReflectionClass($controllerClass);

            if ($reflection->getAttributes(PermissionGroup::class) === []) {
                continue;
            }

            $module = Str::of($reflection->getShortName())
                ->beforeLast('Controller')
                ->kebab()
                ->value();
            $permissions = [];

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $actionAttribute = $method->getAttributes(PermissionAction::class)[0] ?? null;

                if (! $actionAttribute) {
                    continue;
                }

                $action = $actionAttribute->newInstance()->action;
                $permissions["{$module}.{$action}"] = [
                    'name' => "{$module}.{$action}",
                    'action' => $action,
                ];
                // 这里只保留 action 映射用于校验，避免受保护路由与权限发现结果漂移。
                $actions["{$controllerClass}@{$method->getName()}"] = "{$module}.{$action}";
            }

            if ($permissions === []) {
                // 只有 marker 而没有 action 属性的控制器，不会产出权限组。
                continue;
            }

            uasort(
                $permissions,
                // 已知动作保持稳定展示顺序，未知动作则稳定排在后面。
                fn (array $left, array $right): int => [
                    self::ACTION_PRIORITIES[$left['action']] ?? PHP_INT_MAX,
                    $left['action'],
                ] <=> [
                    self::ACTION_PRIORITIES[$right['action']] ?? PHP_INT_MAX,
                    $right['action'],
                ],
            );

            $definitions[$controllerClass] = [
                'module' => $module,
                'permissions' => array_values($permissions),
            ];
        }

        ksort($definitions);
        $this->assertProtectedControllerActionsAreMapped($actions, $protectedControllerActions);

        return [
            'definitions' => array_values($definitions),
        ];
    }

    /**
     * @return array<int, array{0: class-string, 1: string}>
     */
    private function protectedControllerActions(): array
    {
        $controllerActions = [];

        foreach (app('router')->getRoutes() as $route) {
            // 只有挂了控制器权限中间件的路由，才算进权限面。
            if (! in_array(AuthorizeControllerPermission::class, $route->gatherMiddleware(), true)) {
                continue;
            }

            [$controllerClass, $actionMethod] = Str::parseCallback($route->getActionName(), '__invoke');

            if (! $controllerClass || ! $actionMethod) {
                throw new LogicException("Unable to resolve the controller action for protected route [{$route->uri()}].");
            }

            $controllerActions[] = [ltrim($controllerClass, '\\'), $actionMethod];
        }

        return array_values(array_unique($controllerActions, SORT_REGULAR));
    }

    /**
     * @param  array<string, string>  $actions
     * @param  array<int, array{0: class-string, 1: string}>  $protectedControllerActions
     */
    private function assertProtectedControllerActionsAreMapped(array $actions, array $protectedControllerActions): void
    {
        foreach ($protectedControllerActions as [$controllerClass, $actionMethod]) {
            if (isset($actions[ltrim($controllerClass, '\\')."@{$actionMethod}"])) {
                continue;
            }

            // 受保护路由配置错了就尽早失败，并给出最接近问题本身的报错。
            $reflection = new ReflectionClass($controllerClass);

            if (! $reflection->hasMethod($actionMethod)) {
                throw new LogicException("Missing action [{$actionMethod}] on controller [{$controllerClass}].");
            }

            if ($reflection->getAttributes(PermissionGroup::class) === []) {
                throw new LogicException("Missing #[PermissionGroup] on controller [{$controllerClass}].");
            }

            if ($reflection->getMethod($actionMethod)->getAttributes(PermissionAction::class) === []) {
                throw new LogicException("Missing #[PermissionAction] on controller action [{$controllerClass}@{$actionMethod}].");
            }

            // 走到这里说明动作被发现了，但没能推导出稳定权限名。
            throw new LogicException("Missing permission mapping for controller action [{$controllerClass}@{$actionMethod}].");
        }
    }

}
