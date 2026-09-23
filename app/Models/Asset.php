<?php

namespace App\Models;

use App\Enums\AssetType;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property int $user_id
 * @property AssetType $type
 * @property string $brand
 * @property string $model
 * @property string $serial_number
 * @property string|null $ram
 * @property string|null $ram_gb
 * @property string|null $storage
 * @property string $asset_tag
 * @property Carbon|null $acquired_at
 */
#[Fillable([
    'user_id',
    'type',
    'brand',
    'model',
    'serial_number',
    'ram',
    'ram_gb',
    'storage',
    'asset_tag',
    'acquired_at',
])]
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AssetType::class,
            'ram_gb' => 'decimal:2',
            'acquired_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Inspection, $this>
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    /**
     * @return HasOne<Inspection, $this>
     */
    public function latestInspection(): HasOne
    {
        return $this->hasOne(Inspection::class)->latestOfMany('inspection_date');
    }

    /**
     * Preserve inspection history when an asset is removed.
     */
    protected static function booted(): void
    {
        static::deleting(function (Asset $asset): void {
            if ($asset->inspections()->exists()) {
                throw ValidationException::withMessages([
                    'asset' => ['This asset has inspection history and cannot be deleted.'],
                ]);
            }
        });
    }
}
