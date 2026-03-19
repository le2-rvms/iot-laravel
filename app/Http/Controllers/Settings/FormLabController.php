<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreNotificationRuleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class FormLabController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Settings/FormLab', [
            'channelTypes' => [
                ['value' => 'email', 'label' => 'Email'],
                ['value' => 'webhook', 'label' => 'Webhook'],
                ['value' => 'sms', 'label' => 'SMS'],
            ],
            'triggerModes' => [
                ['value' => 'threshold', 'label' => '阈值触发'],
                ['value' => 'schedule', 'label' => '定时触发'],
                ['value' => 'manual', 'label' => '手动触发'],
            ],
        ]);
    }

    public function store(StoreNotificationRuleRequest $request): RedirectResponse
    {
        Log::info('settings.form_lab.submitted', $request->validated());

        return to_route('settings.form-lab')->with('success', '复杂表单示例提交成功。');
    }
}
