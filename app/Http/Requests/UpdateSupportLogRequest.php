<?php

namespace App\Http\Requests;

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

class UpdateSupportLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $supportLog = $this->route('support_log');

        return $user !== null
            && $supportLog instanceof SupportLog
            && Gate::forUser($user)->allows('update', $supportLog);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'ticket_number' => ['prohibited'],
            'created_by' => ['prohibited'],
            'issue_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'initiated_by' => ['sometimes', 'string', 'max:255'],
            'department_id' => [
                'sometimes',
                'integer',
                Rule::exists(Department::class, 'id'),
            ],
            'item_type_id' => [
                'sometimes',
                'integer',
                Rule::exists(ItemType::class, 'id'),
            ],
            'issue_type_ids' => ['sometimes', 'array', 'min:1'],
            'issue_type_ids.*' => [
                'integer',
                'distinct',
                Rule::exists(IssueType::class, 'id'),
            ],
            'description' => ['sometimes', 'string', 'max:20000'],
            'status' => ['sometimes', Rule::enum(SupportLogStatus::class)],
            'priority' => ['sometimes', Rule::in(SupportLogPriority::values())],
            'assigned_to' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'resolution_notes' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'internal_remarks' => ['sometimes', 'nullable', 'string', 'max:20000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var SupportLog $supportLog */
                $supportLog = $this->route('support_log');
                $user = $this->user();
                $currentStatus = $supportLog->status instanceof SupportLogStatus
                    ? $supportLog->status
                    : (SupportLogStatus::tryFrom((string) $supportLog->status) ?? SupportLogStatus::Open);

                if ($user?->isTechnicalResource()) {
                    foreach ([
                        'issue_date',
                        'initiated_by',
                        'department_id',
                        'item_type_id',
                        'issue_type_ids',
                        'description',
                        'priority',
                        'assigned_to',
                    ] as $protectedField) {
                        if ($this->exists($protectedField)) {
                            $validator->errors()->add($protectedField, 'Technical resources may only update status and resolution notes.');
                        }
                    }
                }

                $nextStatus = $this->exists('status')
                    ? SupportLogStatus::tryFrom((string) $this->input('status'))
                    : $currentStatus;

                if ($nextStatus !== null && $nextStatus !== $currentStatus && ! $currentStatus->canTransitionTo($nextStatus)) {
                    $validator->errors()->add('status', "The status cannot transition from {$currentStatus->value} to {$nextStatus->value}.");
                }

                if ($nextStatus !== null && in_array($nextStatus, [SupportLogStatus::Resolved, SupportLogStatus::Closed], true)) {
                    $resolutionNotes = $this->exists('resolution_notes')
                        ? $this->input('resolution_notes')
                        : $supportLog->resolution_notes;
                    if (blank($resolutionNotes)) {
                        $validator->errors()->add('resolution_notes', 'Resolution notes are required when resolving or closing a log.');
                    }
                }

                if (
                    in_array($currentStatus, [SupportLogStatus::Resolved, SupportLogStatus::Closed], true)
                    && $nextStatus === $currentStatus
                    && $this->exists('resolution_notes')
                    && blank($this->input('resolution_notes'))
                ) {
                    $validator->errors()->add('resolution_notes', 'Resolution notes cannot be cleared after a log is resolved or closed.');
                }

                $assignedTo = $this->input('assigned_to');
                if ($this->exists('assigned_to') && $assignedTo !== null && ! $validator->errors()->has('assigned_to')) {
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
