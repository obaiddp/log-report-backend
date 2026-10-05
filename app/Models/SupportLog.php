<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\SupportLogStatus;

class SupportLog extends Model
{
    protected $fillable = [
        'ticket_number',
        'issue_date',
        'initiated_by',
        'department_id',
        'item_type_id',
        'status',
        'issue_details',
        'priority',
        'assigned_to',
        'created_by',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function itemType(): BelongsTo
    {
        return $this->belongsTo(ItemType::class);
    }

    public function issueTypes(): BelongsToMany
    {
        return $this->belongsToMany(IssueType::class, 'support_log_issue_types');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedResource(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SupportLogAssignment::class)->orderBy('assigned_at');
    }
}