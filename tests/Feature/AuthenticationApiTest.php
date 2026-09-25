<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_health_is_public_but_support_logs_require_authentication(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertJsonPath('status', 'ok');
        $this->get('/sanctum/csrf-cookie')->assertNoContent()->assertCookie('XSRF-TOKEN');
        $this->withHeader('Origin', 'http://localhost:5173')
            ->get('/sanctum/csrf-cookie')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
        $this->getJson('/api/v1/support-logs')->assertUnauthorized();
    }

    public function test_login_and_me_return_the_consistent_user_shape(): void
    {
        $user = User::factory()->technicalResource()->create([
            'email' => 'resource@example.test',
            'password' => 'a-secure-test-password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'RESOURCE@example.test',
            'password' => 'a-secure-test-password',
        ])->assertOk()
            ->assertJsonPath('user.email', 'resource@example.test')
            ->assertJsonPath('user.role', 'technical_resource')
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role', 'department_id', 'department', 'status']]);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'resource@example.test');
    }

    public function test_login_endpoint_is_throttled_after_repeated_failures(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'throttle@example.test',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'throttle@example.test',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_inactive_and_unverified_accounts_cannot_log_in(): void
    {
        $inactive = User::factory()->inactive()->create(['password' => 'a-secure-test-password']);
        $unverified = User::factory()->unverified()->create(['password' => 'a-secure-test-password']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $inactive->email,
            'password' => 'a-secure-test-password',
        ])->assertForbidden()->assertJsonPath('error', 'account_inactive');

        $this->postJson('/api/v1/auth/login', [
            'email' => $unverified->email,
            'password' => 'a-secure-test-password',
        ])->assertForbidden()->assertJsonPath('error', 'email_not_verified');
    }

    public function test_logout_invalidates_the_session(): void
    {
        $user = User::factory()->technicalResource()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertFalse(auth()->guard('web')->check());
    }
}
