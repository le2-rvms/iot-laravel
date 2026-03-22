<?php

namespace App\Models\Auth;

use App\Models\Concerns\HasTranslatedAttributesAndUpdatedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property int $id 角色ID
 * @property string $name 角色名称
 * @property string $guard_name Guard名称
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read Collection<int, Permission> $permissions 权限集合
 * @property-read Collection<int, User> $users 绑定用户集合
 */
class Role extends SpatieRole
{
    use HasTranslatedAttributesAndUpdatedBy;
}
