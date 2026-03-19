<?php

namespace App\Models\Auth;

use App\Concerns\ResolvesAttributeLabelsFromDocBlocks;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @property int $id 权限ID
 * @property string $name 权限名称
 * @property string $guard_name Guard名称
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read Collection<int, Role> $roles 绑定角色集合
 */
class Permission extends SpatiePermission
{
    use ResolvesAttributeLabelsFromDocBlocks;
}
