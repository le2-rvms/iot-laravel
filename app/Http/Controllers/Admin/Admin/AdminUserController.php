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
use Inertia\Inertia;
use Inertia\Response;

#[PermissionGroup]
class AdminUserController extends Controller
{
    #[PermissionAction('read')]
    public function index(Request $request): Response
    {
        $query = AdminUser::query()
            // 列表页直接从关系序列化结果里展示角色名。
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

                    // 搜索体验保持简单：一个关键字同时匹配名称和邮箱。
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
            ->withQueryString();

        return Inertia::render('AdminUsers/Index', [
            // filters 和 rows 一起返回，方便 partial reload 时同步更新。
            'filters' => $filters,
            'users' => $adminUsers,
        ]);
    }

    #[PermissionAction('write')]
    public function create(): Response
    {
        return Inertia::render('AdminUsers/Create', [
            // 用户表单只需要角色名列表，更丰富的角色信息仍留在角色管理流里。
            'availableRoles' => AdminRole::availableNames(),
        ]);
    }

    #[PermissionAction('write')]
    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // 模型创建只负责持久化和角色绑定，通知发送时机明确留在控制器层。
        $adminUser = AdminUser::createWithRoles(
            attributes: $request->safe()->except('roles'),
            roles: $validated['roles'] ?? [],
        );

        // 发邮件属于 HTTP 侧副作用，模型只负责用户状态变更。
        $adminUser->sendEmailVerificationNotification();

        return redirect()->action([self::class, 'index'])->with('success', '管理员用户已创建，并已发送验证邮件。');
    }

    #[PermissionAction('write')]
    public function edit(AdminUser $adminUser): Response
    {
        // 编辑页直接消费用户模型和角色关系的序列化结果。
        $adminUser->loadMissing('roles:id,name');

        return Inertia::render('AdminUsers/Edit', [
            'user' => $adminUser,
            'availableRoles' => AdminRole::availableNames(),
        ]);
    }

    #[PermissionAction('write')]
    public function update(UpdateAdminUserRequest $request, AdminUser $adminUser): RedirectResponse
    {
        $validated = $request->safe();

        // 空密码表示“不修改密码”，而不是把密码改成空字符串。
        $attributes = blank($validated['password'] ?? null)
            ? $validated->except(['password', 'roles'])
            : $validated->except('roles');
        $emailChanged = $adminUser->emailWillChange($attributes);

        $adminUser->updateProfile(
            attributes: $attributes,
            roles: $validated['roles'] ?? [],
        );

        if ($emailChanged) {
            // 资料持久化和通知发送是刻意解耦的两步。
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
}
