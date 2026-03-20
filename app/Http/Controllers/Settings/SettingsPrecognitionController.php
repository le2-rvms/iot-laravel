<?php

namespace App\Http\Controllers\Settings;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StorePrecognitionDemoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

#[PermissionGroup('Precognition 表单实验室')]
class SettingsPrecognitionController extends Controller
{
    #[PermissionAction('read')]
    public function index(): Response
    {
        return Inertia::render('Settings/FormLabPrecognition', [
            'channelTypes' => $this->channelTypes(),
        ]);
    }

    #[PermissionAction('write')]
    public function store(StorePrecognitionDemoRequest $request): RedirectResponse
    {
        Log::info('settings.form_lab.precognition_submitted', $request->validated());

        return to_route('precognition.index')->with('success', 'Precognition 示例提交成功。');
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
