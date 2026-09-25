<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
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
        $department = $this->route('department');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department)],
            'code' => ['sometimes', 'nullable', 'string', 'max:20', Rule::unique('departments', 'code')->ignore($department)],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::enum(RecordStatus::class)],
        ];
    }
}
