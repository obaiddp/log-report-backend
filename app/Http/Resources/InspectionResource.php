<?php

namespace App\Http\Resources;

use App\Models\Asset;
use App\Models\TechnicalPersonnel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InspectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'problem_id' => $this->problem_id,
            'asset_id' => $this->asset_id,
            'asset' => $this->whenLoaded('asset', fn (Asset $asset): array => [
                'id' => $asset->id,
                'type' => $asset->type->value,
                'brand' => $asset->brand,
                'model' => $asset->model,
                'serial_number' => $asset->serial_number,
                'asset_tag' => $asset->asset_tag,
            ]),
            'user_id' => $this->asset?->user_id,
            'user' => $this->whenLoaded('asset', function (Asset $asset): ?array {
                if (! $asset->relationLoaded('user')) {
                    return null;
                }

                $user = $asset->user;

                return $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            }),
            'remarks' => $this->remarks,
            'status' => $this->status->value,
            'category' => $this->category->value,
            'sub_category' => $this->sub_category?->value,
            'technical_personnel_id' => $this->technical_personnel_id,
            'technical_personnel' => $this->whenLoaded('technicalPersonnel', fn (?TechnicalPersonnel $personnel): ?array => $personnel === null ? null : [
                'id' => $personnel->id,
                'name' => $personnel->name,
                'email' => $personnel->email,
                'specialization' => $personnel->specialization,
                'status' => $personnel->status->value,
            ]),
            'created_by_id' => $this->created_by,
            'created_by' => $this->whenLoaded('createdBy', fn (?User $user): ?array => $user === null ? null : [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]),
            'inspection_date' => $this->inspection_date->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
