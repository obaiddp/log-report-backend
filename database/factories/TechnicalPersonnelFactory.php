<?php

namespace Database\Factories;

use App\Enums\RecordStatus;
use App\Models\Department;
use App\Models\TechnicalPersonnel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TechnicalPersonnel>
 */
class TechnicalPersonnelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+92-3##-#######'),
            'designation' => fake()->jobTitle(),
            'specialization' => fake()->randomElement(['Hardware', 'Networking', 'Printing', 'Software Support']),
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
