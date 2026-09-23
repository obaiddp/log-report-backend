<?php

namespace App\Http\Requests;

use App\Enums\InspectionCategory;
use App\Enums\InspectionStatus;
use App\Enums\InspectionSubCategory;
use App\Models\Asset;
use App\Models\Inspection;
use App\Models\TechnicalPersonnel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateInspectionRequest extends FormRequest
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
        $inspection = $this->route('inspection');
        $category = $this->input('category', $inspection->category->value);

        return [
            'problem_id' => ['sometimes', 'string', 'max:100', Rule::unique('inspections', 'problem_id')->ignore($inspection)],
            'asset_id' => ['sometimes', 'integer', Rule::exists(Asset::class, 'id')],
            'remarks' => ['sometimes', 'nullable', 'string', 'min:5', 'max:10000'],
            'status' => ['sometimes', Rule::enum(InspectionStatus::class)],
            'category' => ['sometimes', Rule::enum(InspectionCategory::class)],
            'sub_category' => [
                'sometimes',
                'nullable',
                Rule::requiredIf($category === InspectionCategory::Repair->value),
                Rule::enum(InspectionSubCategory::class),
            ],
            'technical_personnel_id' => ['sometimes', Rule::exists(TechnicalPersonnel::class, 'id')],
            'inspection_date' => ['sometimes', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Inspection $inspection */
                $inspection = $this->route('inspection');
                $category = $this->input('category', $inspection->category->value);
                $subCategory = $this->has('sub_category')
                    ? $this->input('sub_category')
                    : $inspection->sub_category?->value;

                if ($category === InspectionCategory::Repair->value && blank($subCategory)) {
                    $validator->errors()->add(
                        'sub_category',
                        'The sub-category field is required for repair inspections.',
                    );
                }

                if (
                    $category === InspectionCategory::NewPurchase->value
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
