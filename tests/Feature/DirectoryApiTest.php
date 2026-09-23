<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Department;
use App\Models\Inspection;
use App\Models\TechnicalPersonnel;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DirectoryApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_create_department(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/departments', [
                'name' => 'Quality Assurance',
                'code' => 'QA',
                'description' => 'Quality and testing operations.',
                'status' => 'active',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('name', 'Quality Assurance')
            ->assertJsonPath('code', 'QA')
            ->assertJsonPath('users_count', 0);
        $this->assertDatabaseHas('departments', [
            'name' => 'Quality Assurance',
            'code' => 'QA',
        ]);
    }

    public function test_departments_can_be_searched_filtered_and_paginated(): void
    {
        $viewer = User::factory()->create();
        Department::factory()->create(['name' => 'Finance Operations']);
        Department::factory()->inactive()->create(['name' => 'Finance Archive']);

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/departments?search=Finance&status=active&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Finance Operations')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_admin_can_create_user_without_exposing_password(): void
    {
        $admin = User::factory()->admin()->create();
        $department = Department::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Field Technician',
                'email' => 'FIELD.TECHNICIAN@EXAMPLE.TEST',
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
                'department_id' => $department->id,
                'designation' => 'Field Technician',
                'territory' => 'Lahore',
                'status' => 'active',
                'role' => UserRole::Technician->value,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('email', 'field.technician@example.test')
            ->assertJsonPath('role', 'technician')
            ->assertJsonMissingPath('password');
        $user = User::query()->where('email', 'field.technician@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('secure-password', $user->password));
    }

    public function test_users_can_be_searched_filtered_and_paginated(): void
    {
        $viewer = User::factory()->create();
        $department = Department::factory()->create();
        User::factory()->technician()->for($department, 'department')->create([
            'name' => 'Matching Technician',
            'territory' => 'Lahore',
        ]);
        User::factory()->for($department, 'department')->create([
            'name' => 'Matching User',
        ]);
        User::factory()->inactive()->create([
            'name' => 'Matching Inactive',
        ]);

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/users?search=Matching&department_id='.$department->id.'&status=active&role=technician&per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Matching Technician')
            ->assertJsonMissingPath('data.0.password')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_admin_can_create_technical_personnel(): void
    {
        $admin = User::factory()->admin()->create();
        $department = Department::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/technical-personnel', [
                'department_id' => $department->id,
                'name' => 'Musa Technical',
                'email' => 'musa@example.test',
                'phone' => '+92-300-1234567',
                'designation' => 'Hardware Technician',
                'specialization' => 'Computer Hardware',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'Musa Technical')
            ->assertJsonPath('department.id', $department->id)
            ->assertJsonPath('specialization', 'Computer Hardware');
        $this->assertDatabaseHas('technical_personnel', [
            'email' => 'musa@example.test',
            'department_id' => $department->id,
        ]);
    }

    public function test_technical_personnel_can_be_searched_filtered_and_paginated(): void
    {
        $viewer = User::factory()->create();
        $department = Department::factory()->create();
        TechnicalPersonnel::factory()->for($department, 'department')->create([
            'name' => 'Network Specialist',
            'specialization' => 'Networking',
        ]);
        TechnicalPersonnel::factory()->inactive()->for($department, 'department')->create([
            'name' => 'Network Specialist Retired',
        ]);

        $this->actingAs($viewer, 'sanctum')
            ->getJson('/api/v1/technical-personnel?search=Network&department_id='.$department->id.'&status=active&per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Network Specialist')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_admin_can_update_user_and_password_is_rehashed(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/users/{$user->id}", [
                'designation' => 'Senior Analyst',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertOk()
            ->assertJsonPath('designation', 'Senior Analyst')
            ->assertJsonMissingPath('password');
        $user->refresh();
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
    }

    public function test_admin_can_delete_unlinked_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('message', 'User deleted successfully.');
        $this->assertModelMissing($user);
    }

    public function test_admin_can_update_department(): void
    {
        $admin = User::factory()->admin()->create();
        $department = Department::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/departments/{$department->id}", [
                'name' => 'Technology Services',
                'status' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Technology Services')
            ->assertJsonPath('status', 'inactive');
        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'name' => 'Technology Services',
            'status' => 'inactive',
        ]);
    }

    public function test_admin_can_delete_unlinked_department(): void
    {
        $admin = User::factory()->admin()->create();
        $department = Department::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/departments/{$department->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Department deleted successfully.');
        $this->assertModelMissing($department);
    }

    public function test_department_with_related_records_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $department = Department::factory()->create();
        TechnicalPersonnel::factory()->for($department, 'department')->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/departments/{$department->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('department');
        $this->assertModelExists($department);
    }

    public function test_admin_can_update_technical_personnel(): void
    {
        $admin = User::factory()->admin()->create();
        $personnel = TechnicalPersonnel::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/technical-personnel/{$personnel->id}", [
                'specialization' => 'Enterprise Networking',
                'status' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('specialization', 'Enterprise Networking')
            ->assertJsonPath('status', 'inactive');
        $this->assertDatabaseHas('technical_personnel', [
            'id' => $personnel->id,
            'specialization' => 'Enterprise Networking',
            'status' => 'inactive',
        ]);
    }

    public function test_admin_can_delete_unassigned_technical_personnel(): void
    {
        $admin = User::factory()->admin()->create();
        $personnel = TechnicalPersonnel::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/technical-personnel/{$personnel->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Technical personnel deleted successfully.');
        $this->assertModelMissing($personnel);
    }

    public function test_personnel_with_inspection_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $personnel = TechnicalPersonnel::factory()->create();
        Inspection::factory()->for($personnel, 'technicalPersonnel')->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/technical-personnel/{$personnel->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('technical_personnel');
        $this->assertModelExists($personnel);
    }

    public function test_user_with_asset_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Asset::factory()->for($user, 'user')->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/users/{$user->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user');
        $this->assertModelExists($user);
    }

    public function test_user_creation_rejects_password_confirmation_mismatch(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Mismatched Password',
                'email' => 'mismatched@example.test',
                'password' => 'secure-password',
                'password_confirmation' => 'different-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_user_creation_rejects_invalid_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Invalid Role',
                'email' => 'invalid-role@example.test',
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
                'role' => 'super-admin',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }
}
