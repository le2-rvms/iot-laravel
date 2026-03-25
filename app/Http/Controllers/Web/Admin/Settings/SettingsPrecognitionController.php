<?php

namespace App\Http\Controllers\Web\Admin\Settings;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Controllers\Web\Admin\Controller;
use App\Http\Requests\Settings\StorePrecognitionDemoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Response;

#[PermissionGroup]
class SettingsPrecognitionController extends Controller
{
    #[PermissionAction('read')]
    public function index(): Response
    {
        return $this->renderPage([
            'channelTypes' => $this->channelTypes(),
        ]);
    }

    #[PermissionAction('write')]
    public function store(StorePrecognitionDemoRequest $request): RedirectResponse
    {
        Log::info('settings.form_lab.precognition_submitted', $request->validated());

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
