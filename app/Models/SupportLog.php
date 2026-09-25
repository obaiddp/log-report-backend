<?php

namespace App\Models;

use App\Enums\SupportLogPriority;
use App\Enums\SupportLogStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property string $ticket_number
 * @property Carbon $issue_date
 * @property string $initiated_by
 * @property int $department_id
 * @property Department $department
 * @property int $item_type_id
 * @property ItemType $item_type
 * @property string $description
 * @property SupportLogStatus $status
 * @property SupportLogPriority $priority
 * @property int|null $assigned_to
 * @property User|null $assignedTo
 * @property int $created_by
 * @property User $creator
 * @property string|null $resolution_notes
 * @property string|null $internal_remarks
 * @property Carbon|null $resolved_at
 * @property Carbon|null $closed_at
 */
#[Fillable([
    'ticket_number',
    'issue_date',
    'initiated_by',
    'department_id',
    'item_type_id',
    'description',
    'status',
    'priority',
    'assigned_to',
    'created_by',
    'resolution_notes',
    'internal_remarks',
    'resolved_at',
    'closed_at',
])]
class SupportLog extends Model
{
    use HasFactory, SoftDeletes;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'open',
        'priority' => 'medium',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'status' => SupportLogStatus::class,
            'priority' => SupportLogPriority::class,
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsToMany<IssueType, $this>
     */
    public function issueTypes(): BelongsToMany
    {
        return $this->belongsToMany(IssueType::class, 'support_log_issue_types')
            ->withPivot(['id'])
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<ItemType, $this>
     */
    public function itemType(): BelongsTo
    {
        return $this->belongsTo(ItemType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->creator();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedResource(): BelongsTo
    {
        return $this->assignedTo();
    }

    /**
     * @return HasMany<SupportLogAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(SupportLogAssignment::class)->orderBy('assigned_at')->orderBy('id');
    }

    public function isAssignedTo(User $user): bool
    {
        return (int) $this->assigned_to === (int) $user->getKey();
    }

    public function transitionTo(SupportLogStatus|string $nextStatus): void
    {
        $next = $nextStatus instanceof SupportLogStatus
            ? $nextStatus
            : SupportLogStatus::from($nextStatus);
        $current = $this->status instanceof SupportLogStatus
            ? $this->status
            : SupportLogStatus::from((string) $this->status);

        if (! $current->canTransitionTo($next)) {
            throw ValidationException::withMessages([
                'status' => ["The status cannot transition from {$current->value} to {$next->value}."],
            ]);
        }

        if ($current === $next) {
            return;
        }

        $now = now();

        if ($next === SupportLogStatus::Resolved) {
            $this->resolved_at ??= $now;
            $this->closed_at = null;
        } elseif ($next === SupportLogStatus::Closed) {
            $this->closed_at = $now;
            $this->resolved_at ??= $now;
        } elseif (! $next->isTerminal() && in_array($current, [
            SupportLogStatus::Resolved,
            SupportLogStatus::Closed,
            SupportLogStatus::Cancelled,
        ], true)) {
            $this->resolved_at = null;
            $this->closed_at = null;
        }

        $this->status = $next;
    }
}
