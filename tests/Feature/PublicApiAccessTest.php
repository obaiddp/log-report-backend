<?php

namespace Tests\Feature;

use App\Enums\AssetType;
use App\Enums\InspectionCategory;
use App\Enums\InspectionStatus;
use App\Enums\InspectionSubCategory;
use App\Models\Asset;
use App\Models\Inspection;
use App\Models\TechnicalPersonnel;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PublicApiAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_health_and_resource_lists_are_public(): void
    {
        $user = User::factory()->create();
        Asset::factory()->for($user, 'user')->create();

        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->getJson('/api/v1/assets')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_asset_creation_is_public(): void
    {
        $owner = User::factory()->create();

        $this->postJson('/api/v1/assets', [
            'user_id' => $owner->id,
            'type' => AssetType::Laptop->value,
            'brand' => 'Dell',
            'model' => 'Latitude 5440',
            'serial_number' => 'PUBLIC-API-001',
            'ram' => '16 GB',
            'ram_gb' => 16,
            'storage' => '512 GB SSD',
            'asset_tag' => 'AST-PUBLIC-001',
            'acquired_at' => '2026-09-23',
        ])
            ->assertCreated()
            ->assertJsonPath('asset_tag', 'AST-PUBLIC-001');
    }

    public function test_inspection_creation_is_public_and_has_no_authenticated_actor(): void
    {
        $asset = Asset::factory()->create();
        $personnel = TechnicalPersonnel::factory()->create();

        $this->postJson('/api/v1/inspections', [
            'problem_id' => 'PRB-PUBLIC-001',
            'asset_id' => $asset->id,
            'remarks' => 'Public inspection workflow.',
            'status' => InspectionStatus::InProgress->value,
            'category' => InspectionCategory::Repair->value,
            'sub_category' => InspectionSubCategory::InHouse->value,
            'technical_personnel_id' => $personnel->id,
            'inspection_date' => '2026-09-23',
        ])
            ->assertCreated()
            ->assertJsonPath('created_by_id', null)
            ->assertJsonPath('created_by', null);

        $this->assertDatabaseHas('inspections', [
            'problem_id' => 'PRB-PUBLIC-001',
            'created_by' => null,
        ]);
    }

    public function test_reports_are_public(): void
    {
        Inspection::factory()->create();

        $this->getJson('/api/v1/reports/summary?range=daily&date=2026-09-23')
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
}
