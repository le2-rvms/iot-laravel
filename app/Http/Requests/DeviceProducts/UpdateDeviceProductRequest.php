<?php

namespace App\Http\Requests\DeviceProducts;

use App\Models\Iot\IotDeviceProduct;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeviceProductRequest extends FormRequest
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
            'product_key' => ['sometimes'],
            'product_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'protocol' => ['nullable', 'string', 'max:64'],
            'category' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return IotDeviceProduct::attributeLabels();
    }

    protected function prepareForValidation(): void
    {
        $this->request->remove('product_key');
    }
}
