<?php

namespace Database\Factories;

use App\Enums\RecordStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make(Str::random(40)),
            'department_id' => Department::factory(),
            'designation' => fake()->jobTitle(),
            'territory' => fake()->city(),
            'status' => RecordStatus::Active,
            'role' => UserRole::TechnicalResource,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Admin,
        ]);
    }

    public function technicalResource(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::TechnicalResource,
        ]);
    }

    public function technician(): static
    {
        return $this->technicalResource();
    }

    public function legacyTechnician(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Technician,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RecordStatus::Inactive,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
