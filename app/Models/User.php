<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'designation',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->role
            ->permissions()
            ->where('name', $permissionName)
            ->exists();
    }

    public function createdSupportLogs(): HasMany
    {
        return $this->hasMany(SupportLog::class, 'created_by');
    }

    public function assignedSupportLogs(): HasMany
    {
        return $this->hasMany(SupportLog::class, 'assigned_to');
    }

    public function assignmentsGiven(): HasMany
    {
        return $this->hasMany(SupportLogAssignment::class, 'assigned_by');
    }

    public function assignmentsReceived(): HasMany
    {
        return $this->hasMany(SupportLogAssignment::class, 'assigned_to');
    }
}