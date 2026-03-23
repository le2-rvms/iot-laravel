<?php

namespace App\Models\Auth;

use App\Models\Concerns\ModelSupport;
use App\Support\PermissionRegistry;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * @property int $id 管理员角色ID
 * @property string $name 管理员角色名称
 * @property string $guard_name Guard名称
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read Collection<int, AdminPermission> $permissions 权限集合
 * @property-read Collection<int, AdminUser> $users 绑定管理员用户集合
 */
class AdminRole extends SpatieRole
{
    use ModelSupport;

    protected $appends = ['is_protected'];

    public static function syncPermissionsAndSuperAdminRole(): self
    {
        $permissionNames = PermissionRegistry::permissionNames();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 控制器 Attribute 才是权限真源，先删除 Spatie 表里的陈旧权限记录。
        AdminPermission::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', $permissionNames)
            ->delete();

        foreach ($permissionNames as $permissionName) {
            AdminPermission::findOrCreate($permissionName, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 受保护角色必须始终和当前权限面保持一致。
        $superAdmin = self::findOrCreate(PermissionRegistry::SUPER_ADMIN_ROLE, 'web');
        $superAdmin->syncPermissions($permissionNames);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $superAdmin;
    }

    /**
     * @return array<int, string>
     */
    public static function availableNames(): array
    {
        return self::query()
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * @param  array{name: string, guard_name?: string}  $attributes
     * @param  array<int, string>  $permissions
     */
    public static function createRoleWithPermissions(array $attributes, array $permissions = []): self
    {
        return DB::transaction(function () use ($attributes, $permissions): self {
            $payload = $attributes;
            $payload['guard_name'] = $attributes['guard_name'] ?? 'web';

            $adminRole = new self($payload);

            $adminRole->save();

            // 角色保存与权限绑定被视为同一个领域操作。
            $adminRole->syncPermissions($permissions);

            return $adminRole->fresh();
        });
    }

    /**
     * @param  array{name?: string}  $attributes
     * @param  array<int, string>  $permissions
     */
    public function updateRole(array $attributes, array $permissions = []): self
    {
        return DB::transaction(function () use ($attributes, $permissions): self {
            // 即使请求里尝试改名，受保护角色也必须保留它的规范名称。
            $this->update([
                'name' => $this->protectedFlag() ? $this->name : ($attributes['name'] ?? $this->name),
            ]);

            // 受保护角色永远拥有控制器发现出来的完整权限集。
            $this->syncPermissions(
                $this->protectedFlag()
                    ? PermissionRegistry::permissionNames()
                    : $permissions,
            );

            return $this->fresh();
        });
    }

    public function deleteRole(): void
    {
        DB::transaction(function (): void {
            // 这个保留角色必须始终存在，因为其它初始化路径会依赖它。
            if ($this->protectedFlag()) {
                throw new LogicException('Super Admin 管理员角色不可删除。');
            }

            // 删除已绑定用户的角色会悄悄剥夺现有管理员权限，因此要显式阻止。
            if ($this->users()->exists()) {
                throw new LogicException('该管理员角色仍有用户绑定，无法删除。');
            }

            $this->delete();
        });
    }

    public function protectedFlag(): bool
    {
        // 当前授权模型里，只有 “Super Admin” 是保留角色名。
        return $this->name === PermissionRegistry::SUPER_ADMIN_ROLE;
    }

    protected function isProtected(): Attribute
    {
        // 直接把保留角色标记暴露给 Inertia，避免控制器再做一层 DTO 变形。
        return Attribute::get(fn (): bool => $this->protectedFlag());
    }
}
