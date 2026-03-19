<?php

namespace App\Http\Controllers;

use App\Models\Auth\User;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = request()->user();

        return Inertia::render('Dashboard', [
            'stats' => [
                'usersCount' => User::count(),
            ],
            'quickLinks' => collect([
                [
                    'title' => '用户管理',
                    'description' => '维护后台用户、邮箱验证状态与基础资料。',
                    'href' => '/users',
                    'permission' => 'users.read',
                ],
                [
                    'title' => '角色权限',
                    'description' => '维护角色与模块读写权限的映射关系。',
                    'href' => '/roles',
                    'permission' => 'roles.read',
                ],
                [
                    'title' => '系统设置',
                    'description' => '查看配置分组与后续扩展入口。',
                    'href' => '/settings',
                    'permission' => 'settings.read',
                ],
            ])->filter(fn (array $link) => $user?->can($link['permission']))
                ->map(fn (array $link) => [
                    'title' => $link['title'],
                    'description' => $link['description'],
                    'href' => $link['href'],
                ])
                ->values()
                ->all(),
            'recentUsers' => Inertia::defer(fn () => User::query()
                ->with('roles:id,name')
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'email', 'email_verified_at', 'created_at'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'verified' => filled($user->email_verified_at),
                    'roles' => $user->roles->pluck('name')->sort()->values()->all(),
                    'created_at' => $user->created_at?->toDateTimeString(),
                ])
                ->all()),
            'systemCards' => Inertia::defer(fn () => [
                [
                    'title' => '权限系统',
                    'description' => '已接入 read/write 权限模型，后续可继续扩展更细业务模块。',
                    'status' => '已接入',
                ],
                [
                    'title' => '监控与队列',
                    'description' => '后续可平滑接入 Horizon、Pulse、Telescope。',
                    'status' => '待接入',
                ],
            ]),
        ]);
    }
}
