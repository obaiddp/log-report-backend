<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\IssueTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property RecordStatus $status
 */
#[Fillable(['name', 'description', 'status'])]
class IssueType extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'active',
    ];

    /** @use HasFactory<IssueTypeFactory> */
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
     * @return BelongsToMany<SupportLog, $this>
     */
    public function supportLogs(): BelongsToMany
    {
        return $this->belongsToMany(SupportLog::class, 'support_log_issue_types')
            ->withPivot(['id'])
            ->withTimestamps();
    }

    protected static function booted(): void
    {
        static::deleting(function (IssueType $issueType): void {
            if ($issueType->supportLogs()->withTrashed()->exists()) {
                throw ValidationException::withMessages([
                    'issue_type' => ['This issue type is linked to support-log history and cannot be deleted.'],
                ]);
            }
        });
    }
}
