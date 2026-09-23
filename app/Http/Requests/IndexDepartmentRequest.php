<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Validation\Rule;

class IndexDepartmentRequest extends IndexRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
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
        return ['name', 'code', 'status', 'created_at', 'updated_at'];
    }
}
