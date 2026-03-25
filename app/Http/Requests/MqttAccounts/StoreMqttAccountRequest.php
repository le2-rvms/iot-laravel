<?php

namespace App\Http\Requests\MqttAccounts;

use App\Models\Iot\IotMqttAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMqttAccountRequest extends FormRequest
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
            'user_name' => ['required', 'string', 'max:64', Rule::unique(IotMqttAccount::class, 'user_name')],
            'password' => ['required', 'string', 'max:255'],
            'clientid' => ['nullable', 'string', 'max:50'],
            'product_key' => ['nullable', 'string', 'max:64'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'certificate' => ['nullable', 'string'],
            // 表单端使用勾选框，但请求层仍收敛到 Laravel 标准 boolean 规则，和 0/1 存储保持一致。
            'is_superuser' => ['required', 'boolean'],
            'enabled' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return IotMqttAccount::attributeLabels();
    }
}
