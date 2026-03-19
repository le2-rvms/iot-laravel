<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreNotificationRuleRequest extends FormRequest
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
            'enabled' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'trigger_mode' => ['required', Rule::in(['threshold', 'schedule', 'manual'])],
            'threshold' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'quiet_hours_enabled' => ['required', 'boolean'],
            'quiet_hours_start' => ['nullable', 'date_format:H:i'],
            'quiet_hours_end' => ['nullable', 'date_format:H:i'],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*.type' => ['required', Rule::in(['email', 'webhook', 'sms'])],
            'channels.*.target' => ['required', 'string', 'max:255'],
            'channels.*.retries' => ['required', 'integer', 'min:0', 'max:10'],
            'channels.*.enabled' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $this->all();

            if (($data['quiet_hours_enabled'] ?? false) && (blank($data['quiet_hours_start'] ?? null) || blank($data['quiet_hours_end'] ?? null))) {
                $validator->errors()->add('quiet_hours_start', '启用静默时段后，必须填写开始和结束时间。');
            }

            if (($data['trigger_mode'] ?? null) === 'threshold' && blank($data['threshold'] ?? null)) {
                $validator->errors()->add('threshold', '阈值触发模式下必须填写阈值。');
            }

            $enabledChannels = collect($this->input('channels', []))->where('enabled', true);

            if ($enabledChannels->isEmpty()) {
                $validator->errors()->add('channels', '至少需要一个启用中的通知渠道。');
            }

            collect($this->input('channels', []))->each(function (array $channel, int $index) use ($validator) {
                $target = $channel['target'] ?? '';
                $type = $channel['type'] ?? '';

                if ($type === 'email' && ! filter_var($target, FILTER_VALIDATE_EMAIL)) {
                    $validator->errors()->add("channels.{$index}.target", 'Email 渠道必须填写合法邮箱地址。');
                }

                if ($type === 'webhook' && ! filter_var($target, FILTER_VALIDATE_URL)) {
                    $validator->errors()->add("channels.{$index}.target", 'Webhook 渠道必须填写合法 URL。');
                }

                if ($type === 'sms' && ! preg_match('/^\\+?[0-9]{6,20}$/', $target)) {
                    $validator->errors()->add("channels.{$index}.target", 'SMS 渠道必须填写合法手机号。');
                }
            });
        });
    }
}
