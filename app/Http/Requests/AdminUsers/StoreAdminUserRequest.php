<?php

namespace App\Http\Requests\AdminUsers;

use App\Models\Auth\AdminRole;
use App\Models\Auth\AdminUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreAdminUserRequest extends FormRequest
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
            'email' => ['required', 'string', 'email:rfc', 'max:255',Rule::unique(AdminUser::class, 'email')],
            'password' => ['required', 'string', Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => [
                'string',
                'distinct',
                Rule::exists(AdminRole::class, 'name')->where('guard_name', 'web'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_merge(AdminUser::attributeLabels(), [
            'roles' => AdminUser::attributeLabels()['roles'],
            'roles.*' => AdminUser::attributeLabels()['roles'],
        ]);
    }
}
