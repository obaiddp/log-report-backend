<?php

namespace Database\Factories;

use App\Enums\SupportLogPriority;
use App\Enums\SupportLogStatus;
use App\Models\Department;
use App\Models\ItemType;
use App\Models\SupportLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupportLog>
 */
class SupportLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_number' => 'ITL-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'issue_date' => now()->subDay()->toDateString(),
            'initiated_by' => fake()->name(),
            'department_id' => Department::factory(),
            'item_type_id' => ItemType::factory(),
            'description' => fake()->paragraph(),
            'status' => SupportLogStatus::Open,
            'priority' => SupportLogPriority::Medium,
            'created_by' => User::factory()->admin(),
            'assigned_to' => null,
            'resolution_notes' => null,
            'internal_remarks' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SupportLogStatus::Resolved,
            'resolution_notes' => fake()->sentence(),
            'resolved_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SupportLogStatus::Closed,
            'resolution_notes' => fake()->sentence(),
            'resolved_at' => now()->subHour(),
            'closed_at' => now(),
        ]);
    }
}
