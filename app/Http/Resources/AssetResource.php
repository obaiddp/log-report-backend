<?php

namespace App\Http\Resources;

use App\Models\Inspection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type->value,
            'brand' => $this->brand,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'ram' => $this->ram,
            'ram_gb' => $this->ram_gb === null ? null : (float) $this->ram_gb,
            'storage' => $this->storage,
            'asset_tag' => $this->asset_tag,
            'acquired_at' => $this->acquired_at?->toDateString(),
            'department_id' => $this->user->department_id,
            'status' => $this->user->status->value,
            'user' => $this->whenLoaded('user', fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]),
            'department' => $this->whenLoaded('user', function (User $user): ?array {
                if (! $user->relationLoaded('department')) {
                    return null;
                }

                $department = $user->department;

                return $department === null ? null : [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                ];
            }),
            'latest_inspection' => $this->whenLoaded(
                'latestInspection',
                fn (?Inspection $inspection): ?array => $inspection === null ? null : $this->inspectionSummary($inspection),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function inspectionSummary(Inspection $inspection): array
    {
        return [
            'id' => $inspection->id,
            'problem_id' => $inspection->problem_id,
            'status' => $inspection->status->value,
            'category' => $inspection->category->value,
            'inspection_date' => $inspection->inspection_date->toDateString(),
        ];
    }
}
