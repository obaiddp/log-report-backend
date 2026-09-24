<?php

namespace Tests\Feature;

use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\Department;
use App\Models\Inspection;
use App\Models\TechnicalPersonnel;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AssetApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_asset(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/assets', [
                'user_id' => $owner->id,
                'type' => AssetType::Laptop->value,
                'brand' => 'Dell',
                'model' => 'Latitude 5440',
                'serial_number' => 'DL-5440-001',
                'ram' => '16 GB',
                'ram_gb' => 16,
                'storage' => '512 GB SSD',
                'asset_tag' => 'AST-CRUD-1',
                'acquired_at' => '2026-09-01',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('asset_tag', 'AST-CRUD-1')
            ->assertJsonPath('ram_gb', 16)
            ->assertJsonPath('department_id', $owner->department_id)
            ->assertJsonPath('user.id', $owner->id);
        $this->assertDatabaseHas('assets', [
            'asset_tag' => 'AST-CRUD-1',
            'serial_number' => 'DL-5440-001',
            'user_id' => $owner->id,
        ]);
    }

    public function test_asset_can_be_filtered_and_paginated(): void
    {
        $viewer = User::factory()->create();
        $department = Department::factory()->create();
        $activeOwner = User::factory()->for($department, 'department')->create([
            'name' => 'Active Owner',
        ]);
        $inactiveOwner = User::factory()->inactive()->for($department, 'department')->create();

        $matchingAsset = Asset::factory()->for($activeOwner, 'user')->create([
            'type' => AssetType::Laptop,
            'asset_tag' => 'AST-SEARCH-MATCH',
            'acquired_at' => '2026-09-10',
        ]);
        Asset::factory()->for($activeOwner, 'user')->create([
            'type' => AssetType::Printer,
            'asset_tag' => 'AST-SEARCH-WRONG-TYPE',
            'acquired_at' => '2026-09-11',
        ]);
        Asset::factory()->for($inactiveOwner, 'user')->create([
            'type' => AssetType::Laptop,
            'asset_tag' => 'AST-SEARCH-WRONG-STATUS',
            'acquired_at' => '2026-09-12',
        ]);

        $this->actingAs($viewer)
            ->getJson('/api/v1/assets?search=SEARCH&type=laptop&department_id='.$department->id.'&user_id='.$activeOwner->id.'&status=active&date_from=2026-09-01&date_to=2026-09-30&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingAsset->id)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_authenticated_user_can_view_asset_with_derived_fields(): void
    {
        $viewer = User::factory()->create();
        $owner = User::factory()->create();
        $asset = Asset::factory()->for($owner, 'user')->create();

        $this->actingAs($viewer)
            ->getJson("/api/v1/assets/{$asset->id}")
            ->assertOk()
            ->assertJsonPath('id', $asset->id)
            ->assertJsonPath('department_id', $owner->department_id)
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('latest_inspection', null);
    }

    public function test_admin_can_update_asset(): void
    {
        $admin = User::factory()->admin()->create();
        $asset = Asset::factory()->create();

        $this->actingAs($admin)
            ->patchJson("/api/v1/assets/{$asset->id}", [
                'brand' => 'Lenovo',
                'ram_gb' => 32,
            ])
            ->assertOk()
            ->assertJsonPath('brand', 'Lenovo')
            ->assertJsonPath('ram_gb', 32);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'brand' => 'Lenovo',
            'ram_gb' => 32,
        ]);
    }

    public function test_admin_can_delete_asset_without_inspections(): void
    {
        $admin = User::factory()->admin()->create();
        $asset = Asset::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/assets/{$asset->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Asset deleted successfully.');
        $this->assertModelMissing($asset);
    }

    public function test_asset_with_inspection_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $asset = Asset::factory()->create();
        $personnel = TechnicalPersonnel::factory()->create();
        Inspection::factory()
            ->for($asset)
            ->for($personnel, 'technicalPersonnel')
            ->for($admin, 'createdBy')
            ->create();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/assets/{$asset->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('asset');
        $this->assertModelExists($asset);
    }

    public function test_asset_creation_validation_returns_422(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/assets', [
                'type' => 'smart_toaster',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'user_id',
                'type',
                'brand',
                'model',
                'serial_number',
                'asset_tag',
            ]);
    }

    public function test_asset_sort_field_allow_list_rejects_injection(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->getJson('/api/v1/assets?sort_by=password&sort_direction=desc')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sort_by');
    }
}
