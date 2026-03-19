<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Settings/Index', [
            'groups' => [
                [
                    'title' => '基础信息与品牌配置',
                    'description' => '后续可接入站点名、品牌文案、页脚信息等后台配置。',
                ],
                [
                    'title' => '邮件与通知配置',
                    'description' => '后续可接入通知频道、邮件模板与重试策略设置。',
                ],
                [
                    'title' => '队列与监控配置',
                    'description' => '已接入 Horizon 面板；Pulse 与 Telescope 仍作为后续扩展项。',
                    'href' => url('/'.trim(config('horizon.path', 'horizon'), '/')),
                    'action_label' => '打开 Horizon',
                    'native' => true,
                ],
                [
                    'title' => '权限与安全配置',
                    'description' => '后续可扩展密码策略、登录限制与权限审计设置。',
                ],
                [
                    'title' => '复杂表单实验室',
                    'description' => '查看 vee-validate + yup + Inertia 的复杂表单接入示例。',
                    'href' => route('settings.form-lab'),
                    'action_label' => '打开实验室',
                    'native' => false,
                ],
            ],
        ]);
    }
}
