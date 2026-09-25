<?php

namespace App\Http\Requests;

use App\Enums\SupportLogPriority;
use App\Enums\SupportLogStatus;
use App\Models\Department;
use App\Models\IssueType;
use App\Models\ItemType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SupportLogAnalyticsRequest extends FormRequest
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
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'department_id' => ['sometimes', 'nullable', 'integer', Rule::exists(Department::class, 'id')],
            'issue_type_id' => ['sometimes', 'nullable', 'integer', Rule::exists(IssueType::class, 'id')],
            'item_type_id' => ['sometimes', 'nullable', 'integer', Rule::exists(ItemType::class, 'id')],
            'status' => ['sometimes', 'nullable', Rule::enum(SupportLogStatus::class)],
            'priority' => ['sometimes', 'nullable', Rule::enum(SupportLogPriority::class)],
            'assigned_to' => ['sometimes', 'nullable', 'integer', Rule::exists(User::class, 'id')],
            'initiated_by' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ticket_number' => ['sometimes', 'nullable', 'string', 'max:40'],
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

                if ($this->filled('date_from') && $this->filled('date_to')) {
                    if (strtotime((string) $this->input('date_to')) < strtotime((string) $this->input('date_from'))) {
                        $validator->errors()->add('date_to', 'The date_to field must be on or after date_from.');
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->validated();
    }
}
