<?php

namespace App\Models\Admin;

use App\Models\Concerns\ModelSupport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @property int $id 管理员权限ID
 * @property string $name 管理员权限名称
 * @property string $guard_name Guard名称
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read Collection<int, AdminRole> $roles 绑定管理员角色集合
 */
class AdminPermission extends SpatiePermission
{
    use ModelSupport;
}
