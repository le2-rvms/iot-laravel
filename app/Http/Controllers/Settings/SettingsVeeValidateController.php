<?php

namespace App\Http\Controllers\Settings;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreNotificationRuleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

#[PermissionGroup]
class SettingsVeeValidateController extends Controller
{
    #[PermissionAction('read')]
    public function index(): Response
    {
        return Inertia::render('Settings/FormLab', [
            'channelTypes' => $this->channelTypes(),
            'triggerModes' => [
                ['value' => 'threshold', 'label' => '阈值触发'],
                ['value' => 'schedule', 'label' => '定时触发'],
                ['value' => 'manual', 'label' => '手动触发'],
            ],
        ]);
    }

    #[PermissionAction('write')]
    public function store(StoreNotificationRuleRequest $request): RedirectResponse
    {
        Log::info('settings.form_lab.submitted', $request->validated());

        return redirect()->action([self::class, 'index'])->with('success', '规则内容已提交。');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function channelTypes(): array
    {
        return [
            ['value' => 'email', 'label' => 'Email'],
            ['value' => 'webhook', 'label' => 'Webhook'],
            ['value' => 'sms', 'label' => 'SMS'],
        ];
    }
}
