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

#[PermissionGroup('角色权限')]
class RoleController extends Controller
{
    #[PermissionAction('read')]
    public function index(): Response
    {
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
                'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
                'is_protected' => $role->name === PermissionRegistry::superAdminRole(),
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
            'permissionGroups' => PermissionRegistry::groupedForFrontend(),
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

        return to_route('roles.index')->with('success', '角色已创建。');
    }

    #[PermissionAction('write')]
    public function edit(Role $role): Response
    {
        $role->loadMissing('permissions:id,name');

        return Inertia::render('Roles/Edit', [
            'permissionGroups' => PermissionRegistry::groupedForFrontend(),
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
                'is_protected' => $role->name === PermissionRegistry::superAdminRole(),
            ],
        ]);
    }

    #[PermissionAction('write')]
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();
        $isProtected = $role->name === PermissionRegistry::superAdminRole();

        $role->update([
            'name' => $isProtected ? $role->name : $validated['name'],
        ]);

        $role->syncPermissions($isProtected ? PermissionRegistry::all() : ($validated['permissions'] ?? []));

        return to_route('roles.edit', $role)->with('success', '角色已更新。');
    }

    #[PermissionAction('write')]
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === PermissionRegistry::superAdminRole()) {
            return to_route('roles.index')->with('error', 'Super Admin 角色不可删除。');
        }

        if ($role->users()->exists()) {
            return to_route('roles.index')->with('error', '该角色仍有用户绑定，无法删除。');
        }

        $role->delete();

        return to_route('roles.index')->with('success', '角色已删除。');
    }
}
