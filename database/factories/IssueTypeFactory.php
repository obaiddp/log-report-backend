<?php

namespace Database\Factories;

use App\Enums\RecordStatus;
use App\Models\IssueType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueType>
 */
class IssueTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'status' => RecordStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RecordStatus::Inactive,
        ]);
    }
}
