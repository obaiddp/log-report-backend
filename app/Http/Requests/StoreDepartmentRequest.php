<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('departments', 'code')],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::enum(RecordStatus::class)],
        ];
    }
}
