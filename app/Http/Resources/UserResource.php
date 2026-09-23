<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'designation' => $this->designation,
            'department_id' => $this->department_id,
            'department' => DepartmentResource::make($this->whenLoaded('department')),
            'territory' => $this->territory,
            'status' => $this->status->value,
            'role' => $this->role->value,
            'assets_count' => $this->whenCounted('assets'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
