<?php

namespace App\Models\Auth;

use App\Models\Concerns\ModelSupport;
use App\Support\ListQueryFilters;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id 管理员用户ID
 * @property string $name 名称
 * @property string $email 邮箱
 * @property Carbon|null $email_verified_at 邮箱验证时间
 * @property string $password 密码
 * @property string|null $remember_token 记住登录令牌
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read Collection<int, AdminRole> $roles 角色
 * @property-read Collection<int, AdminPermission> $permissions 权限集合
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class AdminUser extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, ModelSupport, Notifiable;

    protected $table = 'users';

    protected string $guard_name = 'web';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return Builder<self>
     */
    public static function indexQuery(array $queryParameters): Builder
    {
        $query = self::query()
            ->with('roles:id,name')
            ->latest();

        (new ListQueryFilters(
            query: $queryParameters,
            fieldDefinitions: [
                'name',
                'email',
                'id' => ['integer'],
            ],
            callbacks: [
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);

                    $query->where(function (Builder $nestedQuery) use ($search): void {
                        $nestedQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                },
            ],
        ))->apply($query);

        return $query;
    }

    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function saveAsSuperAdmin(array $attributes): self
    {
        $this->fill([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
        ]);
        $this->forceFill([
            'email_verified_at' => Carbon::now(),
        ])->save();

        // 超级管理员属于运维账号，创建后应立即可用。
        $this->assignSuperAdminRole();

        return $this->fresh();
    }

    public function assignSuperAdminRole(): void
    {
        // 角色同步前会顺带保证底层权限记录已经就位。
        $superAdmin = AdminRole::syncPermissionsAndSuperAdminRole();

        if ($this->hasRole($superAdmin->name)) {
            return;
        }

        $this->syncRoles([$superAdmin->name]);

        $this->refreshAuthorizationState();
    }

    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     * @param  array<int, string>  $roles
     */
    public static function createUserWithRoles(array $attributes, array $roles = []): self
    {
        return DB::transaction(function () use ($attributes, $roles): self {
            $adminUser = (new self)->fill($attributes);

            $adminUser->save();

            // 用户 CRUD 与通知发送解耦，这里只处理持久化和角色关系。
            $adminUser->syncRoles($roles);
            $adminUser->refreshAuthorizationState();

            return $adminUser->fresh();
        });
    }

    /**
     * @param  array{name?: string, email?: string, password?: string}  $attributes
     * @param  array<int, string>  $roles
     */
    public function updateUser(array $attributes, array $roles = []): self
    {
        return DB::transaction(function () use ($attributes, $roles): self {
            if ($this->emailWillChange($attributes)) {
                // email_verified_at 不在 fillable 里，所以合并进 forceFill 一次落库。
                $attributes['email_verified_at'] = null;
            }

            $this->forceFill($attributes)->save();

            // 角色同步放在同一条写路径里，避免调用方自己编排两段操作。
            $this->syncRoles($roles);
            $this->refreshAuthorizationState();

            return $this->fresh();
        });
    }

    /**
     * @param  array{name?: string, email?: string, password?: string}  $attributes
     */
    public function emailWillChange(array $attributes): bool
    {
        return array_key_exists('email', $attributes) && $attributes['email'] !== $this->email;
    }

    public function deleteUser(): void
    {
        DB::transaction(function (): void {
            $this->delete();
        });
    }

    private function refreshAuthorizationState(): void
    {
        // Spatie 会按用户/角色关系缓存权限，因此关系和缓存都要一起刷新。
        $this->unsetRelation('roles');
        $this->unsetRelation('permissions');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
