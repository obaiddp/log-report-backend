<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\TechnicalPersonnelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property int|null $department_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $designation
 * @property string|null $specialization
 * @property RecordStatus $status
 */
#[Table('technical_personnel')]
#[Fillable([
    'department_id',
    'name',
    'email',
    'phone',
    'designation',
    'specialization',
    'status',
])]
class TechnicalPersonnel extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'active',
    ];

    /** @use HasFactory<TechnicalPersonnelFactory> */
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
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return HasMany<Inspection, $this>
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    /**
     * Preserve assignment history when personnel are removed.
     */
    protected static function booted(): void
    {
        static::deleting(function (TechnicalPersonnel $personnel): void {
            if ($personnel->inspections()->exists()) {
                throw ValidationException::withMessages([
                    'technical_personnel' => ['This person is linked to inspections and cannot be deleted.'],
                ]);
            }
        });
    }
}
