<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetInspection extends Model
{
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
        'prepared_by',
        'user_signature',
        'admin_signature',
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

}
