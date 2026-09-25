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
use Illuminate\Validation\ValidationException;

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
        'role' => 'technical_resource',
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    /**
     * @return HasMany<SupportLog, $this>
     */
    public function createdSupportLogs(): HasMany
    {
        return $this->hasMany(SupportLog::class, 'created_by');
    }

    /**
     * @return HasMany<SupportLog, $this>
     */
    public function assignedSupportLogs(): HasMany
    {
        return $this->hasMany(SupportLog::class, 'assigned_to');
    }

    /**
     * @return HasMany<SupportLogAssignment, $this>
     */
    public function supportLogAssignments(): HasMany
    {
        return $this->hasMany(SupportLogAssignment::class, 'assigned_to');
    }

    /**
     * @return HasMany<SupportLogAssignment, $this>
     */
    public function supportLogAssignmentsMade(): HasMany
    {
        return $this->hasMany(SupportLogAssignment::class, 'assigned_by');
    }

    public function isAdmin(): bool
    {
        return $this->canonicalRole() === UserRole::Admin->value;
    }

    public function isTechnicalResource(): bool
    {
        return $this->canonicalRole() === UserRole::TechnicalResource->value;
    }

    /**
     * Backward-compatible alias for the legacy technician terminology.
     */
    public function isTechnician(): bool
    {
        return $this->isTechnicalResource();
    }

    public function canonicalRole(): string
    {
        $role = $this->getAttribute('role');

        return $role instanceof UserRole
            ? $role->canonicalValue()
            : UserRole::normalize($role);
    }

    public function isActive(): bool
    {
        $status = $this->getAttribute('status');

        return ($status instanceof RecordStatus ? $status : RecordStatus::tryFrom((string) $status)) === RecordStatus::Active;
    }

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function canManageInspections(): bool
    {
        return $this->isAdmin() || $this->isTechnicalResource();
    }

    /**
     * Preserve user, asset, inspection, and support-log history during
     * destructive operations.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            if (
                $user->assets()->exists()
                || $user->createdInspections()->exists()
                || $user->createdSupportLogs()->withTrashed()->exists()
                || $user->assignedSupportLogs()->withTrashed()->exists()
                || $user->supportLogAssignments()->exists()
                || $user->supportLogAssignmentsMade()->exists()
            ) {
                throw ValidationException::withMessages([
                    'user' => ['This user is linked to historical records and cannot be deleted.'],
                ]);
            }
        });
    }
}
