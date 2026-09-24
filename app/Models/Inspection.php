<?php

namespace App\Models;

use App\Enums\InspectionCategory;
use App\Enums\InspectionStatus;
use App\Enums\InspectionSubCategory;
use Database\Factories\InspectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $problem_id
 * @property int $asset_id
 * @property string|null $remarks
 * @property InspectionStatus $status
 * @property InspectionCategory $category
 * @property InspectionSubCategory|null $sub_category
 * @property int|null $technical_personnel_id
 * @property int|null $created_by
 * @property Carbon $inspection_date
 */
#[Fillable([
    'problem_id',
    'asset_id',
    'remarks',
    'status',
    'category',
    'sub_category',
    'technical_personnel_id',
    'created_by',
    'inspection_date',
])]
class Inspection extends Model
{
    /** @use HasFactory<InspectionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InspectionStatus::class,
            'category' => InspectionCategory::class,
            'sub_category' => InspectionSubCategory::class,
            'inspection_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<TechnicalPersonnel, $this>
     */
    public function technicalPersonnel(): BelongsTo
    {
        return $this->belongsTo(TechnicalPersonnel::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
