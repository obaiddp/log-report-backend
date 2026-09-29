<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Database\Eloquent\Model;


class Role extends Model
{
    protected $fillable = [
        'name'
    ];

    public function users(): HasMany
    {
        return $this->HasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->BelongsToMany(Permission::class, 'role_permissions');
    }
}
