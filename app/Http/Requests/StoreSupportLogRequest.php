<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use App\Enums\SupportLogPriority;
use App\Enums\SupportLogStatus;
use App\Models\Department;
use App\Models\IssueType;
use App\Models\ItemType;
use App\Models\SupportLog;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSupportLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && Gate::forUser($user)->allows('create', SupportLog::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'ticket_number' => ['prohibited'],
            'created_by' => ['prohibited'],
            'issue_date' => ['required', 'date', 'before_or_equal:today'],
            'initiated_by' => ['required', 'string', 'max:255'],
            'department_id' => [
                'required',
                'integer',
                Rule::exists(Department::class, 'id')->where('status', RecordStatus::Active->value),
            ],
            'item_type_id' => [
                'required',
                'integer',
                Rule::exists(ItemType::class, 'id')->where('status', RecordStatus::Active->value),
            ],
            'issue_type_ids' => ['required', 'array', 'min:1'],
            'issue_type_ids.*' => [
                'integer',
                'distinct',
                Rule::exists(IssueType::class, 'id')->where('status', RecordStatus::Active->value),
            ],
            'description' => ['required', 'string', 'max:20000'],
            'status' => ['sometimes', Rule::enum(SupportLogStatus::class)],
            'priority' => ['sometimes', Rule::enum(SupportLogPriority::class)],
            'assigned_to' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'resolution_notes' => ['nullable', 'string', 'max:20000'],
            'internal_remarks' => ['nullable', 'string', 'max:20000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $user = $this->user();
                $status = SupportLogStatus::tryFrom((string) $this->input('status', SupportLogStatus::Open->value));

                if ($user?->isTechnicalResource() && $status !== SupportLogStatus::Open) {
                    $validator->errors()->add('status', 'Technical resources must create support logs with open status.');
                }

                if ($user?->isTechnicalResource() && $this->filled('assigned_to') && (int) $this->input('assigned_to') !== $user->getKey()) {
                    $validator->errors()->add('assigned_to', 'Technical resources may only assign new logs to themselves.');
                }

                if ($status !== null && in_array($status, [SupportLogStatus::Resolved, SupportLogStatus::Closed], true) && blank($this->input('resolution_notes'))) {
                    $validator->errors()->add('resolution_notes', 'Resolution notes are required for resolved or closed logs.');
                }

                $assignedTo = $this->input('assigned_to');
                if ($assignedTo !== null && ! $validator->errors()->has('assigned_to')) {
                    $assignedUser = User::query()->find($assignedTo);
                    if ($assignedUser === null || ! $assignedUser->isActive() || ! ($assignedUser->isAdmin() || $assignedUser->isTechnicalResource())) {
                        $validator->errors()->add('assigned_to', 'The assignee must be an active administrator or technical resource.');
                    }
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $attributes = [];

        if (! $this->exists('issue_type_ids') && $this->exists('issue_types')) {
            $issueTypes = collect((array) $this->input('issue_types'))
                ->map(static fn (mixed $issueType): mixed => is_array($issueType) ? ($issueType['id'] ?? null) : $issueType)
                ->filter(static fn (mixed $id): bool => $id !== null)
                ->values()
                ->all();
            $attributes['issue_type_ids'] = $issueTypes;
        }

        if (! $this->exists('issue_type_ids') && $this->exists('issue_type_id')) {
            $attributes['issue_type_ids'] = [$this->input('issue_type_id')];
        }

        foreach (['status', 'priority'] as $field) {
            if ($this->exists($field)) {
                $attributes[$field] = mb_strtolower(trim((string) $this->input($field)));
            }
        }

        if ($attributes !== []) {
            $this->merge($attributes);
        }
    }
}
