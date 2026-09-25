<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\ItemTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property RecordStatus $status
 */
#[Fillable(['name', 'description', 'status'])]
class ItemType extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'active',
    ];

    /** @use HasFactory<ItemTypeFactory> */
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
     * @return HasMany<SupportLog, $this>
     */
    public function supportLogs(): HasMany
    {
        return $this->hasMany(SupportLog::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (ItemType $itemType): void {
            if ($itemType->supportLogs()->withTrashed()->exists()) {
                throw ValidationException::withMessages([
                    'item_type' => ['This item type is linked to support-log history and cannot be deleted.'],
                ]);
            }
        });
    }
}
