<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'usersCount' => User::count(),
            ],
            'quickLinks' => [
                [
                    'title' => '用户管理',
                    'description' => '维护后台用户、邮箱验证状态与基础资料。',
                    'href' => '/users',
                ],
            ],
            'recentUsers' => Inertia::defer(fn () => User::query()
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'email', 'email_verified_at', 'created_at'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'verified' => filled($user->email_verified_at),
                    'created_at' => $user->created_at?->toDateTimeString(),
                ])
                ->all()),
            'systemCards' => Inertia::defer(fn () => [
                [
                    'title' => '权限系统',
                    'description' => '为 spatie/laravel-permission 预留导航、控制器与布局接入点。',
                    'status' => '待接入',
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
