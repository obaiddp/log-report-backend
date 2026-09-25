<?php

namespace Database\Factories;

use App\Models\SupportLog;
use App\Models\SupportLogAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportLogAssignment>
 */
class SupportLogAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'support_log_id' => SupportLog::factory(),
            'assigned_to' => User::factory()->technicalResource(),
            'assigned_by' => User::factory()->admin(),
            'assigned_at' => now(),
            'unassigned_at' => null,
        ];
    }
}
