<?php

namespace Tests\Feature;

use App\Enums\AssetType;
use App\Enums\InspectionCategory;
use App\Enums\InspectionStatus;
use App\Enums\InspectionSubCategory;
use App\Models\Asset;
use App\Models\TechnicalPersonnel;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PublicApiAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_health_is_public_but_legacy_resources_require_authentication(): void
    {
        $admin = User::factory()->admin()->create();
        Asset::factory()->for($admin, 'user')->create();

        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->getJson('/api/v1/assets')->assertUnauthorized();
        $this->actingAs($admin)->getJson('/api/v1/assets')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_admin_can_create_an_asset_and_technical_resource_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $payload = [
            'user_id' => $owner->id,
            'type' => AssetType::Laptop->value,
            'brand' => 'Dell',
            'model' => 'Latitude 5440',
            'serial_number' => 'AUTH-API-001',
            'ram' => '16 GB',
            'ram_gb' => 16,
            'storage' => '512 GB SSD',
            'asset_tag' => 'AST-AUTH-001',
            'acquired_at' => '2026-09-23',
        ];

        $this->postJson('/api/v1/assets', $payload)->assertUnauthorized();
        $this->actingAs($admin)->postJson('/api/v1/assets', $payload)
            ->assertCreated()
            ->assertJsonPath('asset_tag', 'AST-AUTH-001');
        $this->actingAs(User::factory()->technicalResource()->create())
            ->postJson('/api/v1/assets', [...$payload, 'serial_number' => 'AUTH-API-002', 'asset_tag' => 'AST-AUTH-002'])
            ->assertForbidden();
    }

    public function test_authenticated_inspection_creation_records_the_authenticated_actor(): void
    {
        $technicalResource = User::factory()->technicalResource()->create();
        $asset = Asset::factory()->create();
        $personnel = TechnicalPersonnel::factory()->create();

        $this->actingAs($technicalResource)->postJson('/api/v1/inspections', [
            'problem_id' => 'PRB-AUTH-001',
            'asset_id' => $asset->id,
            'remarks' => 'Authenticated inspection workflow.',
            'status' => InspectionStatus::InProgress->value,
            'category' => InspectionCategory::Repair->value,
            'sub_category' => InspectionSubCategory::InHouse->value,
            'technical_personnel_id' => $personnel->id,
            'inspection_date' => '2026-09-23',
        ])
            ->assertCreated()
            ->assertJsonPath('created_by_id', $technicalResource->id)
            ->assertJsonPath('created_by.id', $technicalResource->id);

        $this->assertDatabaseHas('inspections', [
            'problem_id' => 'PRB-AUTH-001',
            'created_by' => $technicalResource->id,
        ]);
    }

    public function test_reports_require_an_administrator(): void
    {
        $this->actingAs(User::factory()->technicalResource()->create())
            ->getJson('/api/v1/reports/summary?range=daily&date=2026-09-23')
            ->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
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
}
