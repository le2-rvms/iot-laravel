<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePrecognitionDemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'channel' => ['required', Rule::in(['email', 'webhook', 'sms'])],
            'target' => ['required', 'string', 'max:255'],
            'daily_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '规则名称',
            'email' => '邮箱',
            'channel' => '通知渠道',
            'target' => '渠道目标',
            'daily_limit' => '每日上限',
            'notes' => '备注',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->shouldValidateTargetFormat()) {
                return;
            }

            $channel = $this->input('channel');
            $target = (string) $this->input('target', '');

            if ($target === '') {
                return;
            }

            if ($channel === 'email' && ! filter_var($target, FILTER_VALIDATE_EMAIL)) {
                $validator->errors()->add('target', 'Email 渠道必须填写合法邮箱地址。');
            }

            if ($channel === 'webhook' && ! filter_var($target, FILTER_VALIDATE_URL)) {
                $validator->errors()->add('target', 'Webhook 渠道必须填写合法 URL。');
            }

            if ($channel === 'sms' && ! preg_match('/^\\+?[0-9]{6,20}$/', $target)) {
                $validator->errors()->add('target', 'SMS 渠道必须填写合法手机号。');
            }
        });
    }

    private function shouldValidateTargetFormat(): bool
    {
        if (! $this->isPrecognitive()) {
            return true;
        }

        $validateOnly = $this->header('Precognition-Validate-Only');

        if (! is_string($validateOnly) || $validateOnly === '') {
            return true;
        }

        return collect(explode(',', $validateOnly))
            ->map(fn (string $field) => trim($field))
            ->contains('target');
    }
}
