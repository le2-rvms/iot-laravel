<?php

namespace App\Http\Requests\Users;

use App\Models\Auth\Role;
use App\Models\Auth\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => [
                'string',
                'distinct',
                Rule::exists(Role::class, 'name')->where('guard_name', 'web'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_merge(User::attributeLabels(), [
            'roles' => User::attributeLabels()['roles'],
            'roles.*' => User::attributeLabels()['roles'],
        ]);
    }
}
