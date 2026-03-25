<?php

namespace App\Http\Requests\Devices;

use App\Models\Iot\IotDevice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends FormRequest
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
            'terminal_id' => ['required', 'string', 'max:64', Rule::unique(IotDevice::class, 'terminal_id')],
            'dev_name' => ['required', 'string', 'max:255'],
            'company_id' => ['nullable', 'string', 'max:64'],
            'manufacturer_id' => ['nullable', 'string', 'max:64'],
            'product_key' => ['nullable', 'string', 'max:64'],
            'sim_number' => ['nullable', 'string', 'max:64'],
            '_vehicle_plate' => ['nullable', 'string', 'max:64'],
            '_vehicle_vin' => ['nullable', 'string', 'max:64'],
            '_bind_status' => ['nullable', 'string', 'max:64'],
            'device_status' => ['nullable', 'string', 'max:64'],
            'review_status' => ['nullable', 'string', 'max:64'],
            'auth_code_seed' => ['nullable', 'string', 'max:255'],
            'auth_code_issued_at' => ['nullable', 'date'],
            'auth_code_expires_at' => ['nullable', 'date'],
            'auth_failures' => ['nullable', 'integer', 'min:0'],
            'auth_block_until' => ['nullable', 'date'],
            'city_relation_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return IotDevice::attributeLabels();
    }
}
