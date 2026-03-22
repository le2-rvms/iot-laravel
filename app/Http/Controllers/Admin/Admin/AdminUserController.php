<?php

namespace App\Http\Controllers\Admin\Admin;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUsers\StoreAdminUserRequest;
use App\Http\Requests\AdminUsers\UpdateAdminUserRequest;
use App\Models\Auth\AdminRole;
use App\Models\Auth\AdminUser;
use App\Support\ListQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;

#[PermissionGroup]
class AdminUserController extends Controller
{
    #[PermissionAction('read')]
    public function index(Request $request): Response
    {
        $query = AdminUser::query()
            ->with('roles:id,name')
            ->latest();

        $filters = (new ListQueryFilters(
            request: $request,
            fieldDefinitions: [
                'name',
                'email',
                'id' => ['integer'],
            ],
            callbacks: [
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);

                    $query->where(function (Builder $nestedQuery) use ($search): void {
                        $nestedQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                },
            ],
        ))->apply($query);

        $adminUsers = $query
            ->paginate(10)
            ->withQueryString()
            ->through(fn (AdminUser $adminUser) => [
                'id' => $adminUser->id,
                'name' => $adminUser->name,
                'email' => $adminUser->email,
                'email_verified_at' => $adminUser->email_verified_at?->toDateTimeString(),
                'roles' => $adminUser->roles->pluck('name')->sort()->values()->all(),
                'created_at' => $adminUser->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('AdminUsers/Index', [
            'filters' => $filters,
            'users' => $adminUsers,
        ]);
    }

    #[PermissionAction('write')]
    public function create(): Response
    {
        return Inertia::render('AdminUsers/Create', [
            'availableRoles' => $this->availableRoles(),
        ]);
    }

    #[PermissionAction('write')]
    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $adminUser = AdminUser::create(Arr::except($validated, ['roles']));

        $adminUser->syncRoles($validated['roles'] ?? []);
        $adminUser->sendEmailVerificationNotification();

        return redirect()->action([self::class, 'index'])->with('success', '管理员用户已创建，并已发送验证邮件。');
    }

    #[PermissionAction('write')]
    public function edit(AdminUser $adminUser): Response
    {
        $adminUser->loadMissing('roles:id,name');

        return Inertia::render('AdminUsers/Edit', [
            'user' => [
                'id' => $adminUser->id,
                'name' => $adminUser->name,
                'email' => $adminUser->email,
                'email_verified_at' => $adminUser->email_verified_at?->toDateTimeString(),
                'roles' => $adminUser->roles->pluck('name')->sort()->values()->all(),
            ],
            'availableRoles' => $this->availableRoles(),
        ]);
    }

    #[PermissionAction('write')]
    public function update(UpdateAdminUserRequest $request, AdminUser $adminUser): RedirectResponse
    {
        $validated = $request->validated();
        $emailChanged = array_key_exists('email', $validated) && $validated['email'] !== $adminUser->email;

        if (blank($validated['password'] ?? null)) {
            $validated = Arr::except($validated, ['password']);
        }

        $roles = $validated['roles'] ?? [];
        $validated = Arr::except($validated, ['roles']);

        if ($emailChanged) {
            $adminUser->forceFill([
                'email_verified_at' => null,
            ]);
        }

        $adminUser->update($validated);
        $adminUser->syncRoles($roles);

        if ($emailChanged) {
            $adminUser->save();
            $adminUser->sendEmailVerificationNotification();
        }

        return redirect()->action([self::class, 'edit'], $adminUser)->with('success', '管理员用户信息已更新。');
    }

    #[PermissionAction('write')]
    public function destroy(AdminUser $adminUser): RedirectResponse
    {
        $adminUser->delete();

        return redirect()->action([self::class, 'index'])->with('success', '管理员用户已删除。');
    }

    /**
     * @return array<int, array{name: string}>
     */
    protected function availableRoles(): array
    {
        return AdminRole::query()
            ->orderBy('name')
            ->get(['name'])
            ->map(fn (AdminRole $adminRole) => [
                'name' => $adminRole->name,
            ])
            ->all();
    }
}
