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
                    'title' => '权限系统',
                    'description' => '已接入 read/write 权限模型，后续可继续扩展更细业务模块。',
                    'status' => '已接入',
                ],
                [
                    'title' => '监控与队列',
                    'description' => '已接入 Horizon 队列面板，Pulse 与 Telescope 仍可后续扩展。',
                    'status' => 'Horizon 已接入',
                ],
            ]),
        ]);
    }
}
