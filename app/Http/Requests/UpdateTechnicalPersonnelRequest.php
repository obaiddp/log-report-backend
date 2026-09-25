<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTechnicalPersonnelRequest extends FormRequest
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
        $personnel = $this->route('technical_personnel');

        return [
            'department_id' => ['sometimes', 'nullable', 'integer', Rule::exists(Department::class, 'id')],
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('technical_personnel', 'name')->ignore($personnel)],
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255', Rule::unique('technical_personnel', 'email')->ignore($personnel)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'designation' => ['sometimes', 'nullable', 'string', 'max:255'],
            'specialization' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(RecordStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }
}
