<?php

namespace App\Http\Controllers\Roles;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Models\Auth\Role;
use App\Support\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

#[PermissionGroup]
class RoleController extends Controller
{
    #[PermissionAction('read')]
    public function index(): Response
    {
        $permissionLabels = PermissionRegistry::permissionLabels();

        $roles = Role::query()
            ->withCount('users')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $role->users_count,
                'permissions_count' => $role->permissions->count(),
                'permissions' => $role->permissions
                    ->pluck('name')
                    ->sort()
                    ->map(fn (string $permission) => $permissionLabels[$permission] ?? $permission)
                    ->values()
                    ->all(),
                'is_protected' => $role->name === PermissionRegistry::SUPER_ADMIN_ROLE,
                'created_at' => $role->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
        ]);
    }

    #[PermissionAction('write')]
    public function create(): Response
    {
        return Inertia::render('Roles/Create', [
            'permissionGroups' => PermissionRegistry::definitions(),
        ]);
    }

    #[PermissionAction('write')]
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->action([self::class, 'index'])->with('success', '角色已创建。');
    }

    #[PermissionAction('write')]
    public function edit(Role $role): Response
    {
        $role->loadMissing('permissions:id,name');

        return Inertia::render('Roles/Edit', [
            'permissionGroups' => PermissionRegistry::definitions(),
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
                'is_protected' => $role->name === PermissionRegistry::SUPER_ADMIN_ROLE,
            ],
        ]);
    }

    #[PermissionAction('write')]
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();
        $isProtected = $role->name === PermissionRegistry::SUPER_ADMIN_ROLE;

        $role->update([
            'name' => $isProtected ? $role->name : $validated['name'],
        ]);

        $role->syncPermissions(
            $isProtected
                ? PermissionRegistry::permissionNames()
                : ($validated['permissions'] ?? []),
        );

        return redirect()->action([self::class, 'edit'], $role)->with('success', '角色已更新。');
    }

    #[PermissionAction('write')]
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === PermissionRegistry::SUPER_ADMIN_ROLE) {
            return redirect()->action([self::class, 'index'])->with('error', 'Super Admin 角色不可删除。');
        }

        if ($role->users()->exists()) {
            return redirect()->action([self::class, 'index'])->with('error', '该角色仍有用户绑定，无法删除。');
        }

        $role->delete();

        return redirect()->action([self::class, 'index'])->with('success', '角色已删除。');
    }
}
