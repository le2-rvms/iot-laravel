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

#[PermissionGroup]
class AdminRoleController extends Controller
{
    #[PermissionAction('read')]
    public function index(): Response
    {
        $permissionLabels = PermissionRegistry::permissionLabels();

        $adminRoles = AdminRole::query()
            ->withCount('users')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (AdminRole $adminRole) => [
                'id' => $adminRole->id,
                'name' => $adminRole->name,
                'users_count' => $adminRole->users_count,
                'permissions_count' => $adminRole->permissions->count(),
                'permissions' => $adminRole->permissions
                    ->pluck('name')
                    ->sort()
                    ->map(fn (string $permission) => $permissionLabels[$permission] ?? $permission)
                    ->values()
                    ->all(),
                'is_protected' => $adminRole->name === PermissionRegistry::SUPER_ADMIN_ROLE,
                'created_at' => $adminRole->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('AdminRoles/Index', [
            'roles' => $adminRoles,
        ]);
    }

    #[PermissionAction('write')]
    public function create(): Response
    {
        return Inertia::render('AdminRoles/Create', [
            'permissionGroups' => PermissionRegistry::definitions(),
        ]);
    }

    #[PermissionAction('write')]
    public function store(StoreAdminRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $adminRole = AdminRole::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $adminRole->syncPermissions($validated['permissions'] ?? []);

        return redirect()->action([self::class, 'index'])->with('success', '管理员角色已创建。');
    }

    #[PermissionAction('write')]
    public function edit(AdminRole $adminRole): Response
    {
        $adminRole->loadMissing('permissions:id,name');

        return Inertia::render('AdminRoles/Edit', [
            'permissionGroups' => PermissionRegistry::definitions(),
            'role' => [
                'id' => $adminRole->id,
                'name' => $adminRole->name,
                'permissions' => $adminRole->permissions->pluck('name')->sort()->values()->all(),
                'is_protected' => $adminRole->name === PermissionRegistry::SUPER_ADMIN_ROLE,
            ],
        ]);
    }

    #[PermissionAction('write')]
    public function update(UpdateAdminRoleRequest $request, AdminRole $adminRole): RedirectResponse
    {
        $validated = $request->validated();
        $isProtected = $adminRole->name === PermissionRegistry::SUPER_ADMIN_ROLE;

        $adminRole->update([
            'name' => $isProtected ? $adminRole->name : $validated['name'],
        ]);

        $adminRole->syncPermissions(
            $isProtected
                ? PermissionRegistry::permissionNames()
                : ($validated['permissions'] ?? []),
        );

        return redirect()->action([self::class, 'edit'], $adminRole)->with('success', '管理员角色已更新。');
    }

    #[PermissionAction('write')]
    public function destroy(AdminRole $adminRole): RedirectResponse
    {
        if ($adminRole->name === PermissionRegistry::SUPER_ADMIN_ROLE) {
            return redirect()->action([self::class, 'index'])->with('error', 'Super Admin 管理员角色不可删除。');
        }

        if ($adminRole->users()->exists()) {
            return redirect()->action([self::class, 'index'])->with('error', '该管理员角色仍有用户绑定，无法删除。');
        }

        $adminRole->delete();

        return redirect()->action([self::class, 'index'])->with('success', '管理员角色已删除。');
    }
}
