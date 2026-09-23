<?php

namespace Database\Factories;

use App\Enums\InspectionCategory;
use App\Enums\InspectionStatus;
use App\Enums\InspectionSubCategory;
use App\Models\Asset;
use App\Models\Inspection;
use App\Models\TechnicalPersonnel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inspection>
 */
class InspectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'problem_id' => fake()->unique()->bothify('PRB-#####'),
            'asset_id' => Asset::factory(),
            'remarks' => fake()->optional()->sentence(),
            'status' => fake()->randomElement([
                InspectionStatus::InProgress,
                InspectionStatus::IndoorRepair,
                InspectionStatus::OutdoorRepair,
            ]),
            'category' => InspectionCategory::Repair,
            'sub_category' => fake()->randomElement(InspectionSubCategory::cases()),
            'technical_personnel_id' => TechnicalPersonnel::factory(),
            'created_by' => User::factory(),
            'inspection_date' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
        ];
    }

    public function newPurchase(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => fake()->randomElement([InspectionStatus::Sold, InspectionStatus::InProgress]),
            'category' => InspectionCategory::NewPurchase,
            'sub_category' => null,
        ]);
    }

    public function repair(): static
    {
        return $this->state(fn (array $attributes): array => [
            'category' => InspectionCategory::Repair,
        ]);
    }
}
