<?php

namespace App\Http\Controllers\Web\Admin\Admin;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Controllers\Web\Admin\Controller;
use App\Http\Requests\AdminUsers\StoreAdminUserRequest;
use App\Http\Requests\AdminUsers\UpdateAdminUserRequest;
use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use App\Support\CsvExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[PermissionGroup]
class AdminUserController extends Controller
{
    #[PermissionAction('read')]
    public function index(Request $request): Response
    {
        $query = AdminUser::indexQuery($request->query());
        $filters = $request->except('page');

        $adminUsers = $query
            ->paginate(10)
            ->withQueryString();

        return $this->renderPage([
            // filters 和 rows 一起返回，方便 partial reload 时同步更新。
            'filters' => $filters,
            'users' => $adminUsers,
        ]);
    }

    #[PermissionAction('read')]
    public function export(Request $request): StreamedResponse
    {
        $query = AdminUser::indexQuery($request->query());

        return CsvExporter::download(
            query: $query,
            columns: [
                'models.admin_user.id' => static fn (AdminUser $user): int => $user->id,
                'models.admin_user.name' => static fn (AdminUser $user): string => $user->name,
                'models.admin_user.email' => static fn (AdminUser $user): string => $user->email,
                'models.admin_user.email_verified_label' => static fn (AdminUser $user): string => $user->email_verified_at ? '已验证' : '待验证',
                'models.admin_user.email_verified_at' => static fn (AdminUser $user): string => $user->email_verified_at?->format('Y-m-d H:i:s') ?? '',
                'models.admin_user.roles_display' => static fn (AdminUser $user): string => $user->roles->pluck('name')->implode(', '),
                'models.admin_user.created_at' => static fn (AdminUser $user): string => $user->created_at?->format('Y-m-d H:i:s') ?? '',
            ],
            fileName: 'admin-users-'.now()->format('Ymd-His').'.csv',
        );
    }

    #[PermissionAction('write')]
    public function create(): Response
    {
        return $this->renderPage([
            // 用户表单只需要角色名列表，更丰富的角色信息仍留在角色管理流里。
            'availableRoles' => AdminRole::availableNames(),
        ]);
    }

    #[PermissionAction('write')]
    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $adminUser = AdminUser::createUserWithRoles(
            attributes: $request->safe()->except('roles'),
            roles: $validated['roles'] ?? [],
        );

        // 发邮件属于 HTTP 侧副作用，模型只负责用户状态变更。
        $adminUser->sendEmailVerificationNotification();

        return to_route('admin-users.index')->with('success', '管理员用户已创建，并已发送验证邮件。');
    }

    #[PermissionAction('write')]
    public function edit(AdminUser $adminUser): Response
    {
        // 编辑页直接消费用户模型和角色关系的序列化结果。
        $adminUser->loadMissing('roles:id,name');

        return $this->renderPage([
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

        $adminUser = $adminUser->updateUser(
            attributes: $attributes,
            roles: $validated['roles'] ?? [],
        );

        if ($emailChanged) {
            // 资料持久化和通知发送是刻意解耦的两步。
            $adminUser->sendEmailVerificationNotification();
        }

        return to_route('admin-users.edit', $adminUser)->with('success', '管理员用户信息已更新。');
    }

    #[PermissionAction('write')]
    public function destroy(AdminUser $adminUser): RedirectResponse
    {
        $adminUser->deleteUser();

        return to_route('admin-users.index')->with('success', '管理员用户已删除。');
    }
}
