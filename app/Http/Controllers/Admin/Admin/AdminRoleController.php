<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRoles\StoreAdminRoleRequest;
use App\Http\Requests\AdminRoles\UpdateAdminRoleRequest;
use App\Models\Auth\AdminRole;
use App\Support\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;

#[PermissionGroup]
class AdminRoleController extends Controller
{
    #[PermissionAction('read')]
    public function index(): Response
    {
        $adminRoles = AdminRole::query()
            // 列表页会直接消费用户数量和权限标签，因此在分页结果里一次带齐。
            ->withCount(['users', 'permissions'])
            ->with('permissions:id,name')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('AdminRoles/Index', [
            'roles' => $adminRoles,
            // 页面拿原始权限名，再通过独立的显示文案映射转成中文。
            'permissionDisplayNames' => PermissionRegistry::displayNames(PermissionRegistry::permissionNames()),
        ]);
    }

    #[PermissionAction('write')]
    public function create(): Response
    {
        return Inertia::render('AdminRoles/Create', [
            // 角色表单消费的是分组权限元数据，文案在读取阶段再本地化。
            'permissionGroups' => PermissionRegistry::groups(),
        ]);
    }

    #[PermissionAction('write')]
    public function store(StoreAdminRoleRequest $request): RedirectResponse
    {
        AdminRole::createRoleWithPermissions(
            attributes: $request->safe()->only('name'),
            permissions: $request->validated('permissions') ?? [],
        );

        return redirect()->action([self::class, 'index'])->with('success', '管理员角色已创建。');
    }

    #[PermissionAction('write')]
    public function edit(AdminRole $adminRole): Response
    {
        // 编辑页直接消费模型和关系序列化结果，不再额外组装 DTO。
        $adminRole->loadMissing('permissions:id,name');

        return Inertia::render('AdminRoles/Edit', [
            'permissionGroups' => PermissionRegistry::groups(),
            'role' => $adminRole,
        ]);
    }

    #[PermissionAction('write')]
    public function update(UpdateAdminRoleRequest $request, AdminRole $adminRole): RedirectResponse
    {
        // 受保护角色等领域规则放在模型里，控制器只负责协调请求。
        $adminRole = $adminRole->updateRole(
            attributes: $request->safe()->only('name'),
            permissions: $request->validated('permissions') ?? [],
        );

        return redirect()->action([self::class, 'edit'], $adminRole)->with('success', '管理员角色已更新。');
    }

    #[PermissionAction('write')]
    public function destroy(AdminRole $adminRole): RedirectResponse
    {
        try {
            $adminRole->deleteRole();
        } catch (LogicException $exception) {
            // 领域失败统一转成 flash，前端可以继续停留在列表页。
            return redirect()->action([self::class, 'index'])->with('error', $exception->getMessage());
        }

        return redirect()->action([self::class, 'index'])->with('success', '管理员角色已删除。');
    }
}
