<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'department_id' => ['nullable', 'integer', Rule::exists(Department::class, 'id')],
            'designation' => ['nullable', 'string', 'max:255'],
            'territory' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(RecordStatus::class)],
            'role' => ['sometimes', Rule::in(UserRole::canonicalValues())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $attributes = [];

        if ($this->has('email')) {
            $attributes['email'] = mb_strtolower(trim((string) $this->input('email')));
        }

        if ($this->has('role')) {
            $attributes['role'] = UserRole::normalize($this->input('role'));
        }

        if ($attributes !== []) {
            $this->merge($attributes);
        }
    }
}
