<?php

namespace App\Http\Requests\Settings;

use App\Models\Settings\Config;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSettingRequest extends FormRequest
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
            'key' => ['required', 'string', 'max:255', Rule::unique(Config::class, 'key')],
            'value' => ['required', 'string'],
            'is_masked' => ['required', 'boolean'],
            'remark' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return Config::attributeLabels();
    }
}
