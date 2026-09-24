<?php

namespace Tests\Feature;

use App\Enums\AssetType;
use App\Enums\InspectionCategory;
use App\Enums\InspectionStatus;
use App\Enums\InspectionSubCategory;
use App\Models\Asset;
use App\Models\Department;
use App\Models\Inspection;
use App\Models\TechnicalPersonnel;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InspectionApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_inspection_can_be_created_using_service_mode_alias(): void
    {
        $owner = User::factory()->create();
        $asset = Asset::factory()->for($owner, 'user')->create();
        $personnel = TechnicalPersonnel::factory()->create();

        $response = $this->postJson('/api/v1/inspections', [
            'problem_id' => ' prb-create-1 ',
            'asset_id' => $asset->id,
            'remarks' => 'Battery replacement required.',
            'status' => InspectionStatus::IndoorRepair->value,
            'category' => InspectionCategory::Repair->value,
            'service_mode' => InspectionSubCategory::InHouse->value,
            'technical_personnel_id' => $personnel->id,
            'inspection_date' => '2026-09-23',
            'created_by' => $owner->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('problem_id', 'PRB-CREATE-1')
            ->assertJsonPath('sub_category', 'in_house')
            ->assertJsonMissingPath('service_mode')
            ->assertJsonPath('user_id', $owner->id)
            ->assertJsonPath('created_by_id', null)
            ->assertJsonPath('created_by', null);
        $this->assertDatabaseHas('inspections', [
            'problem_id' => 'PRB-CREATE-1',
            'created_by' => null,
            'sub_category' => 'in_house',
        ]);
    }

    public function test_inspections_can_be_searched_filtered_and_paginated(): void
    {
        $viewer = User::factory()->create();
        $department = Department::factory()->create();
        $owner = User::factory()->for($department, 'department')->create();
        $asset = Asset::factory()->for($owner, 'user')->create([
            'type' => AssetType::Laptop,
            'asset_tag' => 'AST-INSPECTION-MATCH',
        ]);
        $personnel = TechnicalPersonnel::factory()->create();

        $matchingInspection = Inspection::factory()
            ->for($asset)
            ->for($personnel, 'technicalPersonnel')
            ->for($viewer, 'createdBy')
            ->create([
                'problem_id' => 'PRB-FILTER-MATCH',
                'status' => InspectionStatus::IndoorRepair,
                'category' => InspectionCategory::Repair,
                'sub_category' => InspectionSubCategory::InHouse,
                'inspection_date' => '2026-09-20',
            ]);

        Inspection::factory()
            ->for($asset)
            ->for($personnel, 'technicalPersonnel')
            ->for($viewer, 'createdBy')
            ->create([
                'problem_id' => 'PRB-FILTER-WRONG-CATEGORY',
                'status' => InspectionStatus::Sold,
                'category' => InspectionCategory::NewPurchase,
                'sub_category' => null,
                'inspection_date' => '2026-09-20',
            ]);

        $this->actingAs($viewer)
            ->getJson('/api/v1/inspections?search=FILTER-MATCH&status=indoor_repair&type=laptop&department_id='.$department->id.'&user_id='.$owner->id.'&category=repair&technical_personnel_id='.$personnel->id.'&date_from=2026-09-01&date_to=2026-09-30&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingInspection->id)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_authenticated_user_can_view_inspection_summary_relationships(): void
    {
        $viewer = User::factory()->create();
        $inspection = Inspection::factory()->create();
        $inspection->load(['asset.user', 'technicalPersonnel', 'createdBy']);

        $this->actingAs($viewer)
            ->getJson("/api/v1/inspections/{$inspection->id}")
            ->assertOk()
            ->assertJsonPath('id', $inspection->id)
            ->assertJsonPath('asset.asset_tag', $inspection->asset->asset_tag)
            ->assertJsonPath('user.id', $inspection->asset->user_id)
            ->assertJsonPath('technical_personnel.id', $inspection->technical_personnel_id)
            ->assertJsonPath('created_by.id', $inspection->created_by);
    }

    public function test_technician_can_update_inspection(): void
    {
        $technician = User::factory()->technician()->create();
        $inspection = Inspection::factory()->create();

        $this->actingAs($technician)
            ->patchJson("/api/v1/inspections/{$inspection->id}", [
                'status' => InspectionStatus::Sold,
                'remarks' => 'Repair completed.',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'sold')
            ->assertJsonPath('remarks', 'Repair completed.');
        $this->assertDatabaseHas('inspections', [
            'id' => $inspection->id,
            'status' => InspectionStatus::Sold->value,
            'remarks' => 'Repair completed.',
        ]);
    }

    public function test_technician_can_delete_inspection(): void
    {
        $technician = User::factory()->technician()->create();
        $inspection = Inspection::factory()->create();

        $this->actingAs($technician)
            ->deleteJson("/api/v1/inspections/{$inspection->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Inspection deleted successfully.');
        $this->assertModelMissing($inspection);
    }

    public function test_inspection_creation_validation_returns_422(): void
    {
        $technician = User::factory()->technician()->create();

        $this->actingAs($technician)
            ->postJson('/api/v1/inspections', [
                'status' => 'unknown',
                'category' => 'refurbishment',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'problem_id',
                'asset_id',
                'status',
                'category',
                'inspection_date',
            ]);
    }

    public function test_repair_inspection_requires_a_repair_location(): void
    {
        $technician = User::factory()->technician()->create();
        $asset = Asset::factory()->create();
        $personnel = TechnicalPersonnel::factory()->create();

        $this->actingAs($technician)
            ->postJson('/api/v1/inspections', [
                'problem_id' => 'PRB-MISSING-LOCATION',
                'asset_id' => $asset->id,
                'status' => InspectionStatus::IndoorRepair->value,
                'category' => InspectionCategory::Repair->value,
                'technical_personnel_id' => $personnel->id,
                'inspection_date' => '2026-09-23',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sub_category');
    }

    public function test_new_purchase_rejects_a_repair_location(): void
    {
        $technician = User::factory()->technician()->create();
        $asset = Asset::factory()->create();
        $personnel = TechnicalPersonnel::factory()->create();

        $this->actingAs($technician)
            ->postJson('/api/v1/inspections', [
                'problem_id' => 'PRB-PURCHASE-LOCATION',
                'asset_id' => $asset->id,
                'status' => InspectionStatus::Sold->value,
                'category' => InspectionCategory::NewPurchase->value,
                'sub_category' => InspectionSubCategory::InHouse->value,
                'technical_personnel_id' => $personnel->id,
                'inspection_date' => '2026-09-23',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sub_category');
    }

    public function test_duplicate_problem_id_is_rejected(): void
    {
        $technician = User::factory()->technician()->create();
        $inspection = Inspection::factory()->create([
            'problem_id' => 'PRB-DUPLICATE',
        ]);

        $this->actingAs($technician)
            ->postJson('/api/v1/inspections', [
                'problem_id' => 'PRB-DUPLICATE',
                'asset_id' => $inspection->asset_id,
                'status' => InspectionStatus::InProgress->value,
                'category' => InspectionCategory::Repair->value,
                'inspection_date' => '2026-09-23',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('problem_id');
    }
}
