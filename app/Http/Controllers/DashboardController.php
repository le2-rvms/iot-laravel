<?php

namespace App\Http\Controllers;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Models\Auth\User;
use App\Support\NavigationRegistry;
use Inertia\Inertia;
use Inertia\Response;

#[PermissionGroup('仪表盘')]
class DashboardController extends Controller
{
    #[PermissionAction('read')]
    public function __invoke(): Response
    {
        $user = request()->user();

        return Inertia::render('Dashboard', [
            'stats' => [
                'usersCount' => User::count(),
            ],
            'quickLinks' => NavigationRegistry::dashboardQuickLinksFor($user),
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
                    'title' => '权限设置',
                    'description' => '已支持按角色分配查看和维护权限。',
                    'status' => '可用',
                ],
                [
                    'title' => '运行监控',
                    'description' => '队列任务和基础运行状态可持续关注。',
                    'status' => '已开启',
                ],
                [
                    'title' => '消息配置',
                    'description' => '可继续补充通知渠道、模板和发送策略。',
                    'status' => '规划中',
                ],
            ]),
        ]);
    }
}
