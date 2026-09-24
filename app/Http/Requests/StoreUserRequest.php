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
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'department_id' => ['nullable', 'integer', Rule::exists(Department::class, 'id')],
            'designation' => ['nullable', 'string', 'max:255'],
            'territory' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(RecordStatus::class)],
            'role' => ['sometimes', Rule::enum(UserRole::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }
}
