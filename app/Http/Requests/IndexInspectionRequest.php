<?php

namespace App\Http\Requests;

use App\Enums\AssetType;
use App\Enums\InspectionCategory;
use App\Enums\InspectionStatus;
use App\Models\Department;
use App\Models\TechnicalPersonnel;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IndexInspectionRequest extends IndexRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', Rule::enum(InspectionStatus::class)],
            'type' => ['sometimes', 'nullable', Rule::enum(AssetType::class)],
            'department_id' => ['sometimes', 'nullable', 'integer', Rule::exists(Department::class, 'id')],
            'user_id' => ['sometimes', 'nullable', 'integer', Rule::exists(User::class, 'id')],
            'category' => ['sometimes', 'nullable', Rule::enum(InspectionCategory::class)],
            'technical_personnel_id' => ['sometimes', 'nullable', 'integer', Rule::exists(TechnicalPersonnel::class, 'id')],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['date_from', 'date_to'])) {
                    return;
                }

                if ($this->filled('date_from', $this->input('date_to'))
                    && CarbonImmutable::parse((string) $this->input('date_to'))->lt(CarbonImmutable::parse((string) $this->input('date_from')))
                ) {
                    $validator->errors()->add('date_to', 'The date_to field must be on or after date_from.');
                }
            },
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function sortFields(): array
    {
        return [
            'problem_id',
            'status',
            'category',
            'sub_category',
            'inspection_date',
            'created_at',
            'updated_at',
        ];
    }
}
