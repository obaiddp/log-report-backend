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

class SupportLogApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_a_support_log_with_a_generated_ticket_and_issue_types(): void
    {
        $admin = User::factory()->admin()->create();
        $department = Department::factory()->create();
        $itemType = ItemType::factory()->create(['name' => 'Laptop']);
        $issueTypes = IssueType::factory()->count(2)->create();

        $response = $this->actingAs($admin)->postJson('/api/v1/support-logs', $this->payload($department, $itemType, $issueTypes));

        $response->assertCreated()
            ->assertJsonPath('created_by', $admin->id)
            ->assertJsonPath('department.id', $department->id)
            ->assertJsonPath('item_type.id', $itemType->id)
            ->assertJsonCount(2, 'issue_types')
            ->assertJsonMissingPath('password');

        $this->assertStringStartsWith('ITL-', (string) $response->json('ticket_number'));
        $this->assertDatabaseHas('support_logs', [
            'created_by' => $admin->id,
            'department_id' => $department->id,
            'item_type_id' => $itemType->id,
        ]);
        $this->assertDatabaseCount('support_log_issue_types', 2);
    }

    public function test_technical_resource_is_self_assigned_and_can_update_only_an_assigned_log(): void
    {
        $technicalResource = User::factory()->technicalResource()->create();
        $department = Department::factory()->create();
        $itemType = ItemType::factory()->create();
        $issueType = IssueType::factory()->create();

        $created = $this->actingAs($technicalResource)->postJson('/api/v1/support-logs', $this->payload($department, $itemType, [$issueType]));
        $created->assertCreated()->assertJsonPath('assigned_to', $technicalResource->id);

        $logId = $created->json('id');
        $this->actingAs($technicalResource)
            ->patchJson("/api/v1/support-logs/{$logId}", [
                'status' => SupportLogStatus::Resolved->value,
                'resolution_notes' => 'The issue has been fixed.',
            ])
            ->assertOk()
            ->assertJsonPath('status', SupportLogStatus::Resolved->value)
            ->assertJsonPath('resolved_at', fn (mixed $value): bool => is_string($value) && $value !== '');

        $otherTechnicalResource = User::factory()->technicalResource()->create();
        $this->actingAs($otherTechnicalResource)
            ->patchJson("/api/v1/support-logs/{$logId}", ['status' => SupportLogStatus::Closed->value])
            ->assertForbidden();
    }

    public function test_reassignment_closes_the_previous_assignment_and_creates_history(): void
    {
        $admin = User::factory()->admin()->create();
        $first = User::factory()->technicalResource()->create();
        $second = User::factory()->technicalResource()->create();
        $department = Department::factory()->create();
        $itemType = ItemType::factory()->create();
        $issueType = IssueType::factory()->create();

        $created = $this->actingAs($admin)->postJson('/api/v1/support-logs', $this->payload($department, $itemType, [$issueType], $first));
        $created->assertCreated()->assertJsonPath('assigned_to', $first->id);
        $logId = $created->json('id');

        $this->actingAs($admin)
            ->patchJson("/api/v1/support-logs/{$logId}", ['assigned_to' => $second->id])
            ->assertOk()
            ->assertJsonPath('assigned_to', $second->id)
            ->assertJsonCount(2, 'assignments');

        $this->assertDatabaseHas('support_log_assignments', [
            'support_log_id' => $logId,
            'assigned_to' => $first->id,
        ]);
        $this->assertDatabaseHas('support_log_assignments', [
            'support_log_id' => $logId,
            'assigned_to' => $second->id,
        ]);
    }

    public function test_invalid_status_transition_and_future_issue_date_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $department = Department::factory()->create();
        $itemType = ItemType::factory()->create();
        $issueType = IssueType::factory()->create();
        $log = SupportLog::factory()->create([
            'status' => SupportLogStatus::Closed,
            'created_by' => $admin->id,
            'department_id' => $department->id,
            'item_type_id' => $itemType->id,
            'closed_at' => now(),
            'resolved_at' => now(),
            'resolution_notes' => 'Completed.',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/support-logs/{$log->id}", ['status' => SupportLogStatus::Open->value])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->actingAs($admin)
            ->postJson('/api/v1/support-logs', $this->payload($department, $itemType, [$issueType], null, now()->addDay()->toDateString()))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('issue_date');
    }

    public function test_reopening_a_resolved_log_clears_resolution_timestamps_when_work_resumes(): void
    {
        $admin = User::factory()->admin()->create();
        $log = SupportLog::factory()->resolved()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/support-logs/{$log->id}", ['status' => SupportLogStatus::InProgress->value])
            ->assertOk()
            ->assertJsonPath('status', SupportLogStatus::InProgress->value)
            ->assertJsonPath('resolved_at', null)
            ->assertJsonPath('closed_at', null);
    }

    public function test_support_log_list_searches_filters_sorts_and_paginates(): void
    {
        $admin = User::factory()->admin()->create();
        $department = Department::factory()->create();
        $itemType = ItemType::factory()->create();
        $matchingIssue = IssueType::factory()->create(['name' => 'Printer Failure']);
        $otherIssue = IssueType::factory()->create(['name' => 'Network Failure']);

        $matching = SupportLog::factory()->create([
            'created_by' => $admin->id,
            'department_id' => $department->id,
            'item_type_id' => $itemType->id,
            'ticket_number' => 'ITL-FILTER-MATCH',
            'description' => 'Printer queue failure',
            'status' => SupportLogStatus::IndoorRepair,
            'issue_date' => now()->subDays(2)->toDateString(),
        ]);
        $matching->issueTypes()->attach($matchingIssue);
        $wrong = SupportLog::factory()->create([
            'created_by' => $admin->id,
            'department_id' => $department->id,
            'item_type_id' => $itemType->id,
            'ticket_number' => 'ITL-FILTER-WRONG',
            'status' => SupportLogStatus::Open,
            'issue_date' => now()->subDays(2)->toDateString(),
        ]);
        $wrong->issueTypes()->attach($otherIssue);

        $this->actingAs($admin)
            ->getJson('/api/v1/support-logs?search=PRINTER&issue_type_id='.$matchingIssue->id.'&status=indoor_repair&date_from='.now()->subWeek()->toDateString().'&date_to='.now()->toDateString().'&sort_by=ticket_number&sort_direction=asc&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matching->id)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_technical_resources_can_read_safe_form_options_but_not_directory_mutations(): void
    {
        $technicalResource = User::factory()->technicalResource()->create();
        $department = Department::factory()->create();
        $itemType = ItemType::factory()->create();
        $issueType = IssueType::factory()->create();

        $this->actingAs($technicalResource)
            ->getJson('/api/v1/support-log-options')
            ->assertOk()
            ->assertJsonStructure([
                'departments',
                'issue_types',
                'item_types',
                'technical_resources',
            ])
            ->assertJsonFragment(['id' => $department->id])
            ->assertJsonFragment(['id' => $issueType->id])
            ->assertJsonFragment(['id' => $itemType->id]);

        $this->actingAs($technicalResource)
            ->postJson('/api/v1/users', [
                'name' => 'Unauthorized',
                'email' => 'unauthorized@example.test',
                'password' => 'a-secure-test-password',
                'password_confirmation' => 'a-secure-test-password',
            ])
            ->assertForbidden();
    }

    public function test_only_admin_can_archive_and_archived_logs_are_excluded_from_lists(): void
    {
        $admin = User::factory()->admin()->create();
        $technicalResource = User::factory()->technicalResource()->create();
        $department = Department::factory()->create();
        $itemType = ItemType::factory()->create();
        $issueType = IssueType::factory()->create();
        $log = SupportLog::factory()->create([
            'created_by' => $admin->id,
            'assigned_to' => $technicalResource->id,
            'department_id' => $department->id,
            'item_type_id' => $itemType->id,
        ]);

        $this->actingAs($technicalResource)
            ->deleteJson("/api/v1/support-logs/{$log->id}")
            ->assertForbidden();

        $this->actingAs($admin)
            ->deleteJson("/api/v1/support-logs/{$log->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Support log archived successfully.');

        $this->assertSoftDeleted('support_logs', ['id' => $log->id]);
        $this->actingAs($admin)->getJson('/api/v1/support-logs')->assertJsonCount(0, 'data');
    }

    /**
     * @param  iterable<int, IssueType>  $issueTypes
     * @return array<string, mixed>
     */
    private function payload(Department $department, ItemType $itemType, iterable $issueTypes, ?User $assignedTo = null, ?string $issueDate = null): array
    {
        $issueTypeIds = [];
        foreach ($issueTypes as $issueType) {
            $issueTypeIds[] = $issueType->id;
        }

        return [
            'issue_date' => $issueDate ?? now()->subDay()->toDateString(),
            'initiated_by' => 'Test requester',
            'department_id' => $department->id,
            'item_type_id' => $itemType->id,
            'issue_type_ids' => $issueTypeIds,
            'description' => 'A reproducible test support issue.',
            'priority' => 'high',
            ...($assignedTo === null ? [] : ['assigned_to' => $assignedTo->id]),
        ];
    }
}
