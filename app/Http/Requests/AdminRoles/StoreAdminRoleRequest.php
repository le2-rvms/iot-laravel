<?php

namespace App\Http\Requests\AdminRoles;

use App\Models\Admin\AdminPermission;
use App\Models\Admin\AdminRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminRoleRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(AdminRole::class, 'name')->where('guard_name', 'web'),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                'distinct',
                Rule::exists(AdminPermission::class, 'name')->where('guard_name', 'web'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_merge(AdminRole::attributeLabels(), [
            'permissions' => AdminPermission::attributeLabels()['name'],
            'permissions.*' => AdminPermission::attributeLabels()['name'],
        ]);
    }
}
