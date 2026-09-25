<?php

namespace App\Models;

use Database\Factories\SupportLogAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $support_log_id
 * @property int $assigned_to
 * @property int $assigned_by
 * @property Carbon $assigned_at
 * @property Carbon|null $unassigned_at
 */
#[Fillable([
    'support_log_id',
    'assigned_to',
    'assigned_by',
    'assigned_at',
    'unassigned_at',
])]
class SupportLogAssignment extends Model
{
    /** @use HasFactory<SupportLogAssignmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SupportLog, $this>
     */
    public function supportLog(): BelongsTo
    {
        return $this->belongsTo(SupportLog::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedResource(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
