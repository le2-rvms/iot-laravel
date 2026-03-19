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
     *     all: array<int, string>,
     *     frontend: array<int, array{module: string, label: string, permissions: array<int, array{name: string, action: string, action_label: string}>>>,
     *     actions: array<string, string>
     * }|null
     */
    private static ?array $cache = null;

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return self::scan()['all'];
    }

    /**
     * @return array<int, array{module: string, label: string, permissions: array<int, array{name: string, action: string, action_label: string}>}>
     */
    public static function groupedForFrontend(): array
    {
        return self::scan()['frontend'];
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

    public static function permissionForControllerAction(string $controllerClass, string $actionMethod): string
    {
        $actionKey = self::actionKey($controllerClass, $actionMethod);
        $actions = self::scan()['actions'];

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

    /**
     * @return array{
     *     all: array<int, string>,
     *     frontend: array<int, array{module: string, label: string, permissions: array<int, array{name: string, action: string, action_label: string}>>>,
     *     actions: array<string, string>
     * }
     */
    private static function scan(): array
    {
        return self::$cache ??= self::discoverRegistryData();
    }

    /**
     * @return array{
     *     all: array<int, string>,
     *     frontend: array<int, array{module: string, label: string, permissions: array<int, array{name: string, action: string, action_label: string}>>>,
     *     actions: array<string, string>
     * }
     */
    private static function discoverRegistryData(): array
    {
        $controllers = collect(File::allFiles(app_path('Http/Controllers')))
            ->map(fn (\SplFileInfo $file) => self::classFromControllerFile($file->getRealPath()))
            ->filter(fn (string $className) => class_exists($className))
            ->sort()
            ->map(fn (string $className) => self::describeController(new ReflectionClass($className)))
            ->filter()
            ->values();

        return [
            'all' => $controllers
                ->flatMap(fn (array $controller) => collect($controller['permissions'])->pluck('name'))
                ->values()
                ->all(),
            'frontend' => $controllers
                ->map(fn (array $controller) => [
                    'module' => $controller['module'],
                    'label' => $controller['label'],
                    'permissions' => $controller['permissions'],
                ])
                ->values()
                ->all(),
            'actions' => $controllers
                ->flatMap(fn (array $controller) => $controller['actions'])
                ->all(),
        ];
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
            $permission = self::permission(
                self::permissionName($module, $action),
                $action,
                self::ACTION_LABELS[$action],
            );

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
            'label' => $groupAttribute->newInstance()->label,
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
}
