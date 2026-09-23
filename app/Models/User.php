<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property int|null $department_id
 * @property string|null $designation
 * @property string|null $territory
 * @property RecordStatus $status
 * @property UserRole $role
 */
#[Fillable([
    'name',
    'email',
    'password',
    'department_id',
    'designation',
    'territory',
    'status',
    'role',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'active',
        'role' => 'user',
    ];

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => RecordStatus::class,
            'role' => UserRole::class,
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
     * @return HasMany<Asset, $this>
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * @return HasMany<Inspection, $this>
     */
    public function createdInspections(): HasMany
    {
        return $this->hasMany(Inspection::class, 'created_by');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isTechnician(): bool
    {
        return $this->role === UserRole::Technician;
    }

    public function canManageInspections(): bool
    {
        return $this->isAdmin() || $this->isTechnician();
    }

    /**
     * Preserve user, asset, and inspection history during destructive operations.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            if (Auth::check() && Auth::id() === $user->getKey()) {
                throw ValidationException::withMessages([
                    'user' => ['You cannot delete your own account.'],
                ]);
            }

            if ($user->assets()->exists() || $user->createdInspections()->exists()) {
                throw ValidationException::withMessages([
                    'user' => ['This user is linked to assets or inspection history and cannot be deleted.'],
                ]);
            }
        });
    }
}
