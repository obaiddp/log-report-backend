<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\Department;
use Illuminate\Validation\Rule;

class IndexUserRequest extends IndexRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'department_id' => ['sometimes', 'nullable', 'integer', Rule::exists(Department::class, 'id')],
            'status' => ['sometimes', 'nullable', Rule::enum(RecordStatus::class)],
            'role' => ['sometimes', 'nullable', Rule::enum(UserRole::class)],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sortFields(): array
    {
        return ['name', 'email', 'designation', 'territory', 'status', 'role', 'created_at', 'updated_at'];
    }
}
