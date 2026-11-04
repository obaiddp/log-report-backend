<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name',
        'is_head_office',
        'in_use'
    ];

    protected $casts = [
        'is_head_office' => 'boolean',
        'in_use' => 'boolean',
    ];
}