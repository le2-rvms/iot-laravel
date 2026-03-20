<?php

namespace App\Support;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Models\Auth\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use LogicException;
use ReflectionClass;
use ReflectionMethod;

class PermissionRegistry
{
    public const SUPER_ADMIN_ROLE = 'Super Admin';

    /**
     * @var array<string, string>
     */
    private const ACTION_LABELS = [
        'read' => '读取',
        'write' => '写入',
    ];

    /**
     * @var array<string, int>
     */
    private const ACTION_PRIORITIES = [
        'read' => 0,
        'write' => 1,
    ];

    /**
     * @var array{
     *     module: string,
     *     label: string,
     *     permissions: array<int, array{name: string, action: string, action_label: string}>,
     *     actions: array<string, string>
     * }|null
     */
    private static ?array $cache = null;

    /**
     * @return array<int, array{
     *     module: string,
     *     label: string,
     *     permissions: array<int, array{name: string, action: string, action_label: string}>,
     *     actions: array<string, string>
     * }>
     */
    public static function definitions(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $definitions = [];

        foreach (File::allFiles(app_path('Http/Controllers')) as $file) {
            $className = self::classFromControllerFile($file->getRealPath());

            if (! class_exists($className)) {
                continue;
            }

            $definition = self::describeController(new ReflectionClass($className));

            if (! $definition) {
                continue;
            }

            $definitions[$className] = $definition;
        }

        ksort($definitions);

        return self::$cache = array_values($definitions);
    }

    /**
     * @return array<int, string>
     */
    public static function permissionNames(): array
    {
        $names = [];

        foreach (self::definitions() as $definition) {
            foreach ($definition['permissions'] as $permission) {
                $names[] = $permission['name'];
            }
        }

        return $names;
    }

    /**
     * @return array<string, string>
     */
    public static function permissionLabels(): array
    {
        $labels = [];

        foreach (self::definitions() as $definition) {
            foreach ($definition['permissions'] as $permission) {
                $labels[$permission['name']] = "{$definition['label']} · {$permission['action_label']}";
            }
        }

        return $labels;
    }

    /**
     * @return array<string, bool>
     */
    public static function accessMap(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $access = [];

        foreach (self::permissionNames() as $permission) {
            $access[$permission] = $user->can($permission);
        }

        return $access;
    }

    public static function permissionForControllerAction(string $controllerClass, string $actionMethod): string
    {
        $actionKey = self::actionKey($controllerClass, $actionMethod);
        $actions = self::actionPermissions();

        if (isset($actions[$actionKey])) {
            return $actions[$actionKey];
        }

        $reflection = new ReflectionClass($controllerClass);

        if (! $reflection->hasMethod($actionMethod)) {
            throw new LogicException("Missing action [{$actionMethod}] on controller [{$controllerClass}].");
        }

        if ($reflection->getAttributes(PermissionGroup::class) === []) {
            throw new LogicException("Missing #[PermissionGroup] on controller [{$controllerClass}].");
        }

        $actionAttribute = $reflection->getMethod($actionMethod)->getAttributes(PermissionAction::class)[0] ?? null;

        if (! $actionAttribute) {
            throw new LogicException("Missing #[PermissionAction] on controller action [{$controllerClass}@{$actionMethod}].");
        }

        return self::permissionName(
            self::moduleKey($reflection),
            $actionAttribute->newInstance()->action,
        );
    }

    public static function flushCache(): void
    {
        self::$cache = null;
    }

    /**
     * @return array{
     *     module: string,
     *     label: string,
     *     permissions: array<int, array{name: string, action: string, action_label: string}>,
     *     actions: array<string, string>
     * }|null
     */
    private static function describeController(ReflectionClass $reflection): ?array
    {
        $groupAttribute = $reflection->getAttributes(PermissionGroup::class)[0] ?? null;

        if (! $groupAttribute) {
            return null;
        }

        $group = $groupAttribute->newInstance();
        $module = self::moduleKey($reflection);
        $permissions = [];
        $actions = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }

            $actionAttribute = $method->getAttributes(PermissionAction::class)[0] ?? null;

            if (! $actionAttribute) {
                continue;
            }

            $action = $actionAttribute->newInstance()->action;
            $permission = [
                'name' => self::permissionName($module, $action),
                'action' => $action,
                'action_label' => self::ACTION_LABELS[$action],
            ];

            $permissions[$permission['name']] = $permission;
            $actions[self::actionKey($reflection->getName(), $method->getName())] = $permission['name'];
        }

        if ($permissions === []) {
            return null;
        }

        uasort(
            $permissions,
            fn (array $left, array $right) => self::ACTION_PRIORITIES[$left['action']] <=> self::ACTION_PRIORITIES[$right['action']],
        );

        return [
            'module' => $module,
            'label' => $group->label,
            'permissions' => array_values($permissions),
            'actions' => $actions,
        ];
    }

    private static function classFromControllerFile(string $path): string
    {
        $relativePath = Str::after($path, app_path().DIRECTORY_SEPARATOR);

        return 'App\\'.str_replace(
            [DIRECTORY_SEPARATOR, '.php'],
            ['\\', ''],
            $relativePath,
        );
    }

    private static function moduleKey(ReflectionClass $reflection): string
    {
        return Str::of($reflection->getShortName())
            ->beforeLast('Controller')
            ->kebab()
            ->value();
    }

    private static function permissionName(string $module, string $action): string
    {
        return "{$module}.{$action}";
    }

    private static function actionKey(string $controllerClass, string $actionMethod): string
    {
        $controllerClass = ltrim($controllerClass, '\\');

        return "{$controllerClass}@{$actionMethod}";
    }

    /**
     * @return array<string, string>
     */
    private static function actionPermissions(): array
    {
        $actions = [];

        foreach (self::definitions() as $definition) {
            $actions += $definition['actions'];
        }

        return $actions;
    }
}
