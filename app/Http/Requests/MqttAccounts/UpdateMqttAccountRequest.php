<?php

namespace App\Http\Requests\MqttAccounts;

use App\Models\Iot\MqttAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMqttAccountRequest extends FormRequest
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
            'user_name' => [
                'required',
                'string',
                'max:64',
                Rule::unique(MqttAccount::class, 'user_name')->ignore($this->route('mqtt_account'), 'act_id'),
            ],
            // 编辑页留空表示不修改密码，因此这里和创建请求不同，允许 nullable。
            'password' => ['nullable', 'string', 'max:255'],
            'clientid' => ['nullable', 'string', 'max:50'],
            'product_key' => ['nullable', 'string', 'max:64'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'certificate' => ['nullable', 'string'],
            'is_superuser' => ['required', 'boolean'],
            'enabled' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return MqttAccount::attributeLabels();
    }
}
