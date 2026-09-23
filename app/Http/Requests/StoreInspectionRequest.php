<?php

namespace App\Http\Requests;

use App\Enums\InspectionCategory;
use App\Enums\InspectionStatus;
use App\Enums\InspectionSubCategory;
use App\Models\Asset;
use App\Models\TechnicalPersonnel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInspectionRequest extends FormRequest
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
            'problem_id' => ['required', 'string', 'max:100', Rule::unique('inspections', 'problem_id')],
            'asset_id' => ['required', 'integer', Rule::exists(Asset::class, 'id')],
            'remarks' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::enum(InspectionStatus::class)],
            'category' => ['required', Rule::enum(InspectionCategory::class)],
            'sub_category' => [
                'nullable',
                Rule::requiredIf($this->input('category') === InspectionCategory::Repair->value),
                Rule::enum(InspectionSubCategory::class),
            ],
            'technical_personnel_id' => ['required', 'integer', Rule::exists(TechnicalPersonnel::class, 'id')],
            'inspection_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    ! $validator->errors()->has('category')
                    && $this->input('category') === InspectionCategory::NewPurchase->value
                    && $this->filled('sub_category')
                ) {
                    $validator->errors()->add(
                        'sub_category',
                        'The sub-category field is only valid for repair inspections.',
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $attributes = [];

        if ($this->has('problem_id')) {
            $attributes['problem_id'] = mb_strtoupper(trim((string) $this->input('problem_id')));
        }

        if (! $this->filled('sub_category') && $this->filled('service_mode')) {
            $attributes['sub_category'] = mb_strtolower(
                str_replace(['-', ' '], '_', trim((string) $this->input('service_mode'))),
            );
        }

        if ($attributes !== []) {
            $this->merge($attributes);
        }
    }
}
