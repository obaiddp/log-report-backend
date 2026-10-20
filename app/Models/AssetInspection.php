<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetInspection extends Model
{
    public const APPROVAL_PENDING_ADMIN = 'pending_admin';
    public const APPROVAL_COMPLETED = 'completed';
    public const APPROVAL_RETURNED = 'returned';

    protected $fillable = [
        'username',
        'designation',
        'email',
        'cell',
        'department_id',
        'territory',
        'status',
        'item_type_id',
        'model',
        'brand',
        'serial_number',
        'item_tag',
        'ram',
        'generation',
        'hard_drive',
        'item_problem',
        'it_remarks',
        'admin_remarks',
        'approval_status',
        'prepared_by',
        'admin_user',
        'prepared_by_digital_signature',
        'user_digital_signature',
        'admin_digital_signature',
        'prepared_signed_at',
        'user_signed_at',
        'admin_signed_at',
        'return_reason',
    ];

    protected $casts = [
        'prepared_by_digital_signature' => 'boolean',
        'user_digital_signature' => 'boolean',
        'admin_digital_signature' => 'boolean',
        'prepared_signed_at' => 'datetime',
        'user_signed_at' => 'datetime',
        'admin_signed_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function itemType(): BelongsTo
    {
        return $this->belongsTo(ItemType::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user');
    }

    public function isCompleted(): bool
    {
        return $this->approval_status === self::APPROVAL_COMPLETED;
    }
}