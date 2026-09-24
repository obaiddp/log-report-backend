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
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_report_summary_returns_frontend_friendly_metrics_and_arrays(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-23 12:00:00'));
        $admin = User::factory()->admin()->create();
        $department = Department::factory()->create(['name' => 'Operations']);
        $owner = User::factory()->for($department, 'department')->create();
        $firstAsset = Asset::factory()->for($owner, 'user')->create([
            'type' => AssetType::Laptop,
            'ram_gb' => 8,
        ]);
        $secondAsset = Asset::factory()->for($owner, 'user')->create([
            'type' => AssetType::Laptop,
            'ram_gb' => 16,
        ]);
        $firstPersonnel = TechnicalPersonnel::factory()->create(['name' => 'Alpha Technician']);
        $secondPersonnel = TechnicalPersonnel::factory()->create(['name' => 'Beta Technician']);

        Inspection::factory()->for($firstAsset)->for($firstPersonnel, 'technicalPersonnel')->for($admin, 'createdBy')->create([
            'problem_id' => 'PRB-RPT-1',
            'status' => InspectionStatus::IndoorRepair,
            'category' => InspectionCategory::Repair,
            'sub_category' => InspectionSubCategory::InHouse,
            'inspection_date' => '2026-09-23',
        ]);
        Inspection::factory()->for($secondAsset)->for($firstPersonnel, 'technicalPersonnel')->for($admin, 'createdBy')->create([
            'problem_id' => 'PRB-RPT-2',
            'status' => InspectionStatus::Sold,
            'category' => InspectionCategory::NewPurchase,
            'sub_category' => null,
            'inspection_date' => '2026-09-22',
        ]);
        Inspection::factory()->for($firstAsset)->for($secondPersonnel, 'technicalPersonnel')->for($admin, 'createdBy')->create([
            'problem_id' => 'PRB-RPT-3',
            'status' => InspectionStatus::InProgress,
            'category' => InspectionCategory::Repair,
            'sub_category' => InspectionSubCategory::OutHouse,
            'inspection_date' => '2026-09-21',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/reports/summary?date_from=2026-09-21&date_to=2026-09-23&department_id='.$department->id)
            ->assertOk()
            ->assertJsonPath('period.range', 'custom')
            ->assertJsonPath('period.from', '2026-09-21')
            ->assertJsonPath('period.to', '2026-09-23')
            ->assertJsonPath('period.granularity', 'daily')
            ->assertJsonPath('metrics.total_assets', 2)
            ->assertJsonPath('metrics.total_users', 1)
            ->assertJsonPath('metrics.total_inspections', 3)
            ->assertJsonPath('metrics.total_new_purchases', 1)
            ->assertJsonPath('metrics.total_repairs', 2)
            ->assertJsonPath('metrics.pending_inspections', 2)
            ->assertJsonPath('asset_distribution.0.type', 'laptop')
            ->assertJsonPath('asset_distribution.0.count', 2)
            ->assertJsonPath('ram_usage_by_department.0.ram_gb', 24)
            ->assertJsonPath('ram_usage_by_department.0.average_ram_gb', 12)
            ->assertJsonPath('purchase_vs_repair.0.category', 'new_purchase')
            ->assertJsonPath('purchase_vs_repair.0.count', 1)
            ->assertJsonPath('inspection_trend.0.count', 1)
            ->assertJsonPath('inspection_trend.1.count', 1)
            ->assertJsonPath('inspection_trend.2.count', 1)
            ->assertJsonPath('technician_workload.0.technical_personnel_id', $firstPersonnel->id)
            ->assertJsonPath('technician_workload.0.count', 2)
            ->assertJsonPath('technician_workload.1.technical_personnel_id', $secondPersonnel->id)
            ->assertJsonPath('technician_workload.1.count', 1)
            ->assertJsonCount(4, 'status_breakdown')
            ->assertJsonCount(5, 'asset_distribution')
            ->assertJsonCount(2, 'purchase_vs_repair');
    }

    public function test_daily_report_accepts_date_and_defaults_to_today(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-23 12:00:00'));
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/reports/summary?range=daily&date=2026-09-22')
            ->assertOk()
            ->assertJsonPath('period.from', '2026-09-22')
            ->assertJsonPath('period.to', '2026-09-22')
            ->assertJsonCount(1, 'inspection_trend');
    }

    public function test_report_export_filters_rows_and_prevents_csv_formula_injection(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $asset = Asset::factory()->for($owner, 'user')->create([
            'asset_tag' => 'AST-CSV-1',
        ]);
        $personnel = TechnicalPersonnel::factory()->create(['name' => 'CSV Technician']);
        Inspection::factory()->for($asset)->for($personnel, 'technicalPersonnel')->for($admin, 'createdBy')->create([
            'problem_id' => 'PRB-CSV-1',
            'remarks' => '=2+2',
            'inspection_date' => '2026-09-23',
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/reports/export?date_from=2026-09-23&date_to=2026-09-23&technical_personnel_id='.$personnel->id);

        $response
            ->assertOk()
            ->assertDownload('asset-inspections-20260923-20260923.csv')
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('PRB-CSV-1', $content);
        $this->assertStringContainsString(",'=2+2", $content);
        $this->assertStringNotContainsString(',=2+2', $content);
    }

    public function test_report_rejects_invalid_or_conflicting_periods(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/reports/summary?range=monthly')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('range');
    }

    public function test_report_requires_both_explicit_period_dates(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson('/api/v1/reports/summary?date_from=2026-09-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_to');
    }
}
