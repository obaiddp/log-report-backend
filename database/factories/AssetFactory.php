<?php

namespace Database\Factories;

use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ramGb = fake()->randomElement([4, 8, 16, 32]);

        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(AssetType::cases()),
            'brand' => fake()->randomElement(['Dell', 'HP', 'Lenovo', 'Canon', 'Epson', 'Acer']),
            'model' => fake()->bothify('??-###'),
            'serial_number' => fake()->unique()->bothify('SN-##########'),
            'ram' => "{$ramGb} GB",
            'ram_gb' => $ramGb,
            'storage' => fake()->randomElement(['256 GB SSD', '512 GB SSD', '1 TB HDD']),
            'asset_tag' => fake()->unique()->bothify('AST-####'),
            'acquired_at' => fake()->optional()->date(),
        ];
    }
}
