<?php

namespace App\Support;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Models\Auth\User;
use Illuminate\Support\Str;
use LogicException;
use ReflectionClass;

class PermissionRegistry
{
    public const SUPER_ADMIN_ROLE = 'Super Admin';

    /**
     * @var array{
     *     definitions: array<int, array{
     *         module: string,
     *         permissions: array<int, array{name: string, action: string}>
     *     }>
     * }|null
     */
    private static ?array $cache = null;

    /**
     * @return array<int, array{
     *     module: string,
     *     label: string,
     *     permissions: array<int, array{name: string, action: string, action_label: string}>
     * }>
     */
    public static function definitions(): array
    {
        return app(PermissionLocalizer::class)->definitions(self::structure()['definitions']);
    }

    /**
     * @return array<int, string>
     */
    public static function permissionNames(): array
    {
        $names = [];

        foreach (self::structure()['definitions'] as $definition) {
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
        return app(PermissionLocalizer::class)->permissionLabels(self::structure()['definitions']);
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
        $controllerClass = ltrim($controllerClass, '\\');
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

        $module = Str::of($reflection->getShortName())
            ->beforeLast('Controller')
            ->kebab()
            ->value();

        return "{$module}.{$actionAttribute->newInstance()->action}";
    }

    /**
     * @return array{
     *     definitions: array<int, array{
     *         module: string,
     *         permissions: array<int, array{name: string, action: string}>
     *     }>
     * }
     */
    private static function structure(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        return self::$cache = app(PermissionStructureBuilder::class)->build();
    }
}
