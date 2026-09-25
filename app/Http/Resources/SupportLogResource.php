<?php

namespace App\Http\Resources;

use App\Models\SupportLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'issue_date' => $this->issue_date->toDateString(),
            'initiated_by' => $this->initiated_by,
            'department_id' => $this->department_id,
            'department' => DepartmentResource::make($this->whenLoaded('department')),
            'issue_types' => IssueTypeResource::collection($this->whenLoaded('issueTypes')),
            'item_type_id' => $this->item_type_id,
            'item_type' => ItemTypeResource::make($this->whenLoaded('itemType')),
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'assigned_to' => $this->assigned_to,
            'assigned_resource' => UserResource::make($this->whenLoaded('assignedTo')),
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'resolution_notes' => $this->resolution_notes,
            'internal_remarks' => $this->canViewInternalRemarks($request) ? $this->internal_remarks : null,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'assignments' => SupportLogAssignmentResource::collection($this->whenLoaded('assignments')),
        ];
    }

    private function canViewInternalRemarks(Request $request): bool
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->isAdmin()
            || ($user->isTechnicalResource() && $this->resource instanceof SupportLog && $this->resource->isAssignedTo($user));
    }
}
