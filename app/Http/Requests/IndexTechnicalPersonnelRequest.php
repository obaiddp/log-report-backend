<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use App\Models\Department;
use Illuminate\Validation\Rule;

class IndexTechnicalPersonnelRequest extends IndexRequest
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
        ];
    }

    protected function defaultSortBy(): string
    {
        return 'name';
    }

    protected function defaultSortDirection(): string
    {
        return 'asc';
    }

    /**
     * @return array<int, string>
     */
    protected function sortFields(): array
    {
        return ['name', 'email', 'phone', 'designation', 'specialization', 'status', 'created_at', 'updated_at'];
    }
}
