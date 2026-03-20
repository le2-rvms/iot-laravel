<?php

namespace App\Http\Requests\Settings;

use App\Models\Settings\Config;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
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
        /** @var Config|null $config */
        $config = $this->route('config');

        return [
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Config::class, 'key')->ignore($config?->id),
            ],
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
