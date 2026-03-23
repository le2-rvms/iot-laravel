<?php

namespace App\Support;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Models\Auth\AdminUser;
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
    public static function groups(): array
    {
        // 运行时结构保持与语言无关，只有展示给 UI 时才补上文案。
        return app(PermissionLocalizer::class)->groups(self::structure()['definitions']);
    }

    /**
     * @return array<int, string>
     */
    public static function permissionNames(): array
    {
        $names = [];

        // 持久化和授权都依赖稳定权限名，因此要把分组结构拍平成名称列表。
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
    public static function displayNames(array $permissionNames): array
    {
        return app(PermissionLocalizer::class)->displayNames($permissionNames);
    }

    public static function displayName(string $permissionName): string
    {
        return app(PermissionLocalizer::class)->displayName($permissionName);
    }

    /**
     * @return array<string, bool>
     */
    public static function accessMap(?AdminUser $user): array
    {
        if (! $user) {
            return [];
        }

        $access = [];

        // 前端访问控制始终以稳定权限名为 key，而不是本地化文案。
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

        // 中间件鉴权只按当前 action 即时推导权限，不再扫描所有控制器。
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

        // 权限定义只会随代码变化，因此按进程做缓存就足够了。
        return self::$cache = app(PermissionStructureBuilder::class)->build();
    }
}
