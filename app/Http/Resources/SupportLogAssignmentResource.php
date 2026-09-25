<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportLogAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'support_log_id' => $this->support_log_id,
            'assigned_to' => $this->assigned_to,
            'assigned_resource' => UserResource::make($this->whenLoaded('assignedResource')),
            'assigned_by' => $this->assigned_by,
            'assigned_by_user' => UserResource::make($this->whenLoaded('assigner')),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'unassigned_at' => $this->unassigned_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
