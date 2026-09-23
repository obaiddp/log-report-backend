<?php

namespace App\Http\Requests;

use App\Enums\AssetType;
use App\Enums\RecordStatus;
use App\Models\Department;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IndexAssetRequest extends IndexRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'nullable', Rule::enum(AssetType::class)],
            'department_id' => ['sometimes', 'nullable', 'integer', Rule::exists(Department::class, 'id')],
            'user_id' => ['sometimes', 'nullable', 'integer', Rule::exists(User::class, 'id')],
            'status' => ['sometimes', 'nullable', Rule::enum(RecordStatus::class)],
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
            'asset_tag',
            'type',
            'brand',
            'model',
            'serial_number',
            'ram_gb',
            'acquired_at',
            'created_at',
            'updated_at',
        ];
    }
}
