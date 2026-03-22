<?php

namespace App\Models\Auth;

use App\Models\Concerns\HasTranslatedAttributesAndUpdatedBy;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
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
    use HasFactory, HasRoles, Notifiable, HasTranslatedAttributesAndUpdatedBy;

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
}
