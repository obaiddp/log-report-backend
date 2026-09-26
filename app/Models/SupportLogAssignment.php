<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportLogAssignment extends Model
{
    protected $fillable = [
        'support_log_id',
        'assigned_by',
        'assigned_to',
        'assigned_at',
        'unassigned_at'
    ];

    public function supportLog()
    {
        return $this->belongsTo(SupportLog::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}