<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Validation\Rule;

class IndexIssueTypeRequest extends IndexRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

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

    /**
     * @return array<int, string>
     */
    protected function sortFields(): array
    {
        return ['name', 'status', 'created_at', 'updated_at'];
    }
}
