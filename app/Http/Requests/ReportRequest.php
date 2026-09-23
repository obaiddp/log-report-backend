<?php

namespace App\Http\Requests;

use App\Enums\AssetType;
use App\Enums\InspectionCategory;
use App\Enums\InspectionStatus;
use App\Enums\ReportRange;
use App\Models\Department;
use App\Models\TechnicalPersonnel;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReportRequest extends FormRequest
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
            'range' => ['sometimes', 'nullable', Rule::enum(ReportRange::class)],
            'date' => ['sometimes', 'nullable', 'date'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'nullable', Rule::enum(AssetType::class)],
            'department_id' => ['sometimes', 'nullable', 'integer', Rule::exists(Department::class, 'id')],
            'user_id' => ['sometimes', 'nullable', 'integer', Rule::exists(User::class, 'id')],
            'status' => ['sometimes', 'nullable', Rule::enum(InspectionStatus::class)],
            'category' => ['sometimes', 'nullable', Rule::enum(InspectionCategory::class)],
            'technical_personnel_id' => ['sometimes', 'nullable', 'integer', Rule::exists(TechnicalPersonnel::class, 'id')],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['range', 'date', 'date_from', 'date_to'])) {
                    return;
                }

                $hasNamedPeriod = $this->filled('range') || $this->filled('date');
                $hasFrom = $this->filled('date_from');
                $hasTo = $this->filled('date_to');

                if (! $hasNamedPeriod && $hasFrom !== $hasTo) {
                    $validator->errors()->add('date_from', 'Specify both date_from and date_to when using an explicit period.');
                    $validator->errors()->add('date_to', 'Specify both date_from and date_to when using an explicit period.');
                }

                if ($hasNamedPeriod && ($hasFrom || $hasTo)) {
                    $validator->errors()->add('date_from', 'Do not combine date_from or date_to with range or date.');
                    $validator->errors()->add('date_to', 'Do not combine date_from or date_to with range or date.');
                }

                if ($this->input('range') === ReportRange::Weekly->value && $this->filled('date')) {
                    $validator->errors()->add('date', 'The date field cannot be used with the weekly range.');
                }

                if ($hasFrom && $hasTo && CarbonImmutable::parse((string) $this->input('date_to'))->lt(CarbonImmutable::parse((string) $this->input('date_from')))) {
                    $validator->errors()->add('date_to', 'The date_to field must be on or after date_from.');
                }
            },
        ];
    }

    public function searchTerm(): ?string
    {
        $search = trim((string) $this->validated('search', ''));

        return $search === '' ? null : $search;
    }

    /**
     * @return array{from: CarbonImmutable, to: CarbonImmutable, granularity: string, requested_range: string}
     */
    public function period(): array
    {
        $range = ReportRange::tryFrom((string) $this->validated('range', '')) ?? ReportRange::Daily;
        $now = CarbonImmutable::now();

        if ($this->filled('date_from') && $this->filled('date_to')) {
            return [
                'from' => CarbonImmutable::parse((string) $this->validated('date_from'))->startOfDay(),
                'to' => CarbonImmutable::parse((string) $this->validated('date_to'))->endOfDay(),
                'granularity' => 'daily',
                'requested_range' => 'custom',
            ];
        }

        if ($range === ReportRange::Weekly) {
            return [
                'from' => $now->startOfWeek(),
                'to' => $now->endOfWeek(),
                'granularity' => 'weekly',
                'requested_range' => $range->value,
            ];
        }

        $date = $this->filled('date')
            ? CarbonImmutable::parse((string) $this->validated('date'))
            : $now;

        return [
            'from' => $date->startOfDay(),
            'to' => $date->endOfDay(),
            'granularity' => 'daily',
            'requested_range' => $range->value,
        ];
    }
}
