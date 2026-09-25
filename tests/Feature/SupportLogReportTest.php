<?php

namespace Tests\Feature;

use App\Enums\SupportLogStatus;
use App\Models\Department;
use App\Models\IssueType;
use App\Models\ItemType;
use App\Models\SupportLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SupportLogReportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_dashboard_and_relational_reports_are_aggregate_endpoints(): void
    {
        $admin = User::factory()->admin()->create();
        $resource = User::factory()->technicalResource()->create();
        $department = Department::factory()->create(['name' => 'Field Operations']);
        $itemType = ItemType::factory()->create();
        $issueType = IssueType::factory()->create(['name' => 'Network Connectivity']);

        $open = SupportLog::factory()->create([
            'created_by' => $admin->id,
            'assigned_to' => $resource->id,
            'department_id' => $department->id,
            'item_type_id' => $itemType->id,
            'description' => '=2+2 printer queue failure',
            'status' => SupportLogStatus::Open,
        ]);
        $open->issueTypes()->attach($issueType);
        SupportLog::factory()->resolved()->create([
            'created_by' => $admin->id,
            'assigned_to' => $resource->id,
            'department_id' => $department->id,
            'item_type_id' => $itemType->id,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/v1/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('metrics.total', 2)
            ->assertJsonPath('metrics.open', 1)
            ->assertJsonPath('metrics.resolved', 1);

        $this->actingAs($admin)
            ->getJson('/api/v1/reports/by-department?date_from='.now()->subYear()->toDateString())
            ->assertOk()
            ->assertJsonPath('data.0.department', 'Field Operations')
            ->assertJsonPath('data.0.total', 2);

        $this->actingAs($admin)
            ->getJson('/api/v1/reports/by-issue-type')
            ->assertOk()
            ->assertJsonPath('data.0.issue_type', 'Network Connectivity')
            ->assertJsonPath('data.0.count', 1);

        $this->actingAs($admin)
            ->getJson('/api/v1/reports/by-status')
            ->assertOk()
            ->assertJsonCount(7, 'data');

        $this->actingAs($admin)
            ->getJson('/api/v1/reports/by-item')
            ->assertOk()
            ->assertJsonPath('data.0.item_type_id', $itemType->id)
            ->assertJsonPath('data.0.count', 2);

        $export = $this->actingAs($admin)
            ->getJson('/api/v1/reports/export?date_from='.now()->subYear()->toDateString().'&date_to='.now()->addDay()->toDateString());
        $export->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $export->streamedContent();
        $this->assertStringContainsString('Network Connectivity', $content);
        $this->assertStringContainsString("'=2+2", $content);
    }

    public function test_technical_resources_cannot_access_sensitive_analytics(): void
    {
        $this->actingAs(User::factory()->technicalResource()->create())
            ->getJson('/api/v1/dashboard/summary')
            ->assertForbidden();
    }
}
