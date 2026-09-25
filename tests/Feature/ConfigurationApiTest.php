<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\IssueType;
use App\Models\ItemType;
use App\Models\SupportLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ConfigurationApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_manage_issue_types_and_technical_resources_cannot(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/issue-types', [
                'name' => 'Software Failure',
                'description' => 'Application failure.',
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'Software Failure');

        $issueType = IssueType::query()->where('name', 'Software Failure')->firstOrFail();
        $this->actingAs($admin)
            ->patchJson("/api/v1/issue-types/{$issueType->id}", ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('status', 'inactive');

        $this->actingAs(User::factory()->technicalResource()->create())
            ->postJson('/api/v1/issue-types', ['name' => 'Unauthorized'])
            ->assertForbidden();
    }

    public function test_technical_resource_can_read_support_log_lookup_options_without_directory_mutation_access(): void
    {
        $department = Department::factory()->create();
        $technicalResource = User::factory()->technicalResource()->for($department)->create();
        IssueType::factory()->create(['name' => 'Lookup Issue']);
        ItemType::factory()->create(['name' => 'Lookup Item']);

        $this->actingAs($technicalResource)
            ->getJson('/api/v1/support-log-options')
            ->assertOk()
            ->assertJsonPath('departments.0.id', $department->id)
            ->assertJsonPath('issue_types.0.name', 'Lookup Issue')
            ->assertJsonPath('item_types.0.name', 'Lookup Item')
            ->assertJsonCount(1, 'technical_resources')
            ->assertJsonPath('technical_resources.0.id', $technicalResource->id);

        $this->actingAs($technicalResource)
            ->postJson('/api/v1/issue-types', ['name' => 'Still Forbidden'])
            ->assertForbidden();
        $this->actingAs($technicalResource)
            ->postJson('/api/v1/users', [
                'name' => 'Forbidden Promotion',
                'email' => 'forbidden-promotion@example.test',
                'password' => 'a-secure-test-password',
                'password_confirmation' => 'a-secure-test-password',
                'role' => 'admin',
            ])
            ->assertForbidden();
    }

    public function test_configuration_with_support_log_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $issueType = IssueType::factory()->create();
        $itemType = ItemType::factory()->create();
        $log = SupportLog::factory()->create([
            'created_by' => $admin->id,
            'item_type_id' => $itemType->id,
        ]);
        $log->issueTypes()->attach($issueType);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/issue-types/{$issueType->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('issue_type');
        $this->actingAs($admin)->deleteJson("/api/v1/support-logs/{$log->id}")->assertOk();
        $this->actingAs($admin)
            ->deleteJson("/api/v1/issue-types/{$issueType->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('issue_type');
        $this->actingAs($admin)
            ->deleteJson("/api/v1/item-types/{$itemType->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('item_type');
    }

    public function test_admin_can_create_a_user_with_a_hashed_password_and_canonical_role(): void
    {
        $admin = User::factory()->admin()->create();
        $department = Department::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/users', [
                'name' => 'New Resource',
                'email' => 'new-resource@example.test',
                'password' => 'a-secure-test-password',
                'password_confirmation' => 'a-secure-test-password',
                'department_id' => $department->id,
                'role' => 'technical_resource',
            ])
            ->assertCreated()
            ->assertJsonPath('role', 'technical_resource')
            ->assertJsonMissingPath('password');

        $user = User::query()->where('email', 'new-resource@example.test')->firstOrFail();
        $this->assertNotSame('a-secure-test-password', $user->password);
        $this->assertTrue($user->isVerified());
    }
}
