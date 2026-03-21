<?php

namespace App\Models\Auth;

use App\Concerns\ResolvesAttributeLabelsFromDocBlocks;
use App\Concerns\TracksUpdatedBy;
use Database\Factories\UserFactory;
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
 * @property int $id 用户ID
 * @property string $name 名称
 * @property string $email 邮箱
 * @property Carbon|null $email_verified_at 邮箱验证时间
 * @property string $password 密码
 * @property string|null $remember_token 记住登录令牌
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read Collection<int, Role> $roles 角色
 * @property-read Collection<int, Permission> $permissions 权限集合
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, ResolvesAttributeLabelsFromDocBlocks, TracksUpdatedBy;

    protected string $guard_name = 'web';

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

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
