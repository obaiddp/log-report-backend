<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property RecordStatus $status
 */
#[Fillable(['name', 'code', 'description', 'status'])]
class Department extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'active',
    ];

    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<TechnicalPersonnel, $this>
     */
    public function technicalPersonnel(): HasMany
    {
        return $this->hasMany(TechnicalPersonnel::class);
    }

    /**
     * @return HasMany<SupportLog, $this>
     */
    public function supportLogs(): HasMany
    {
        return $this->hasMany(SupportLog::class);
    }

    /**
     * Ensure departments are not removed while related records exist.
     */
    protected static function booted(): void
    {
        static::deleting(function (Department $department): void {
            if (
                $department->users()->exists()
                || $department->technicalPersonnel()->exists()
                || $department->supportLogs()->withTrashed()->exists()
            ) {
                throw ValidationException::withMessages([
                    'department' => ['This department still has users, personnel, or support-log history.'],
                ]);
            }
        });
    }
}
