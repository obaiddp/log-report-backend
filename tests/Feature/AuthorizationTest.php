<?php

namespace Tests\Feature;

use App\Enums\InspectionCategory;
use App\Enums\InspectionStatus;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\TechnicalPersonnel;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function nonAdminRoles(): array
    {
        return [
            'technician' => [UserRole::Technician->value],
            'user' => [UserRole::User->value],
        ];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_roles_receive_403_when_managing_assets(string $role): void
    {
        $user = User::factory()->create([
            'role' => $role,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/assets', [])
            ->assertForbidden()
            ->assertJsonPath('message', 'You do not have permission to perform this action.');
    }

    public function test_regular_user_receives_403_when_creating_inspections(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/inspections', [])
            ->assertForbidden();
    }

    public function test_regular_user_can_read_assets(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
        ]);
        Asset::factory()->for($user, 'user')->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/assets')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_technician_can_create_inspection(): void
    {
        $technician = User::factory()->technician()->create();
        $asset = Asset::factory()->create();
        $personnel = TechnicalPersonnel::factory()->create();

        $response = $this->actingAs($technician, 'sanctum')
            ->postJson('/api/v1/inspections', [
                'problem_id' => 'PRB-AUTH-1',
                'asset_id' => $asset->id,
                'status' => InspectionStatus::IndoorRepair->value,
                'category' => InspectionCategory::Repair->value,
                'sub_category' => 'in_house',
                'technical_personnel_id' => $personnel->id,
                'inspection_date' => '2026-09-23',
                'created_by' => $asset->user_id,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('created_by_id', $technician->id)
            ->assertJsonPath('created_by.id', $technician->id)
            ->assertJsonPath('problem_id', 'PRB-AUTH-1');
        $this->assertDatabaseHas('inspections', [
            'problem_id' => 'PRB-AUTH-1',
            'created_by' => $technician->id,
        ]);
    }

    public function test_technician_can_read_reports(): void
    {
        $technician = User::factory()->technician()->create();

        $this->actingAs($technician, 'sanctum')
            ->getJson('/api/v1/reports/summary?range=daily&date=2026-09-23')
            ->assertOk()
            ->assertJsonStructure([
                'period',
                'metrics',
                'status_breakdown',
                'asset_distribution',
                'ram_usage_by_department',
                'purchase_vs_repair',
                'inspection_trend',
                'technician_workload',
            ]);
    }

    public function test_regular_user_receives_403_when_reading_reports(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/reports/summary?range=daily')
            ->assertForbidden();
    }
}
