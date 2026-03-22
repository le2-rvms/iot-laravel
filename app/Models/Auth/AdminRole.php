<?php

namespace App\Models\Auth;

use App\Models\Concerns\HasTranslatedAttributesAndUpdatedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role as SpatieRole;

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
    use HasTranslatedAttributesAndUpdatedBy;
}
