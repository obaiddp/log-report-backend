<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemType extends Model
{
    protected $fillable = [
        'name',
        'has_specification'
    ];

    protected function casts(): array
    {
        return [
            'has_specification' => 'boolean',
        ];
    }

    public function supportLogs(): HasMany
    {
        return $this->HasMany(SupportLog::class);        
    }    
}