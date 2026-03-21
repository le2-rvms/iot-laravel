<?php

namespace App\Http\Requests\Mqtt;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmqxAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'max:255'],
            // 先与后台管理和 mqtt_accounts 表结构对齐，避免未来启用 clientid 绑定时出现接口契约分裂。
            'clientid' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        // 对外保持旧系统的 ignore 契约，EMQX 侧无需区分 Laravel 默认验证响应格式。
        throw new HttpResponseException(response()->json([
            'result' => 'ignore',
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 400));
    }
}
