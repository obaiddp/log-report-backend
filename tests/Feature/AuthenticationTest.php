<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_csrf_cookie_endpoint_initializes_spa_session(): void
    {
        $this->withHeader('Referer', 'http://localhost:5173')
            ->get('/sanctum/csrf-cookie')
            ->assertNoContent()
            ->assertCookie('XSRF-TOKEN');
    }

    public function test_spa_origin_is_allowed_with_credentials(): void
    {
        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'GET',
        ])
            ->options('/sanctum/csrf-cookie')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_me_returns_401_without_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_valid_credentials_authenticate_the_user(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'admin@example.test',
            'password' => 'secret-password',
        ]);

        $response = $this->withHeader('Referer', 'http://localhost:5173')
            ->postJson('/api/v1/auth/login', [
                'email' => 'ADMIN@EXAMPLE.TEST',
                'password' => 'secret-password',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.email', 'admin@example.test')
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonMissingPath('user.password');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_return_422_validation_errors(): void
    {
        User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'correct-password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.test',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
        $this->assertTrue(Auth::guard('web')->guest());
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@example.test',
            'password' => 'secret-password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.test',
            'password' => 'secret-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
        $this->assertTrue(Auth::guard('web')->guest());
    }

    public function test_inactive_user_with_existing_session_is_forbidden(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account is inactive.');
    }

    public function test_authenticated_user_can_retrieve_profile_without_sensitive_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email)
            ->assertJsonMissingPath('password')
            ->assertJsonMissingPath('remember_token');
    }

    public function test_user_can_logout_and_session_is_invalidated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->withHeader('Referer', 'http://localhost:5173')
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');
        $this->assertTrue(Auth::guard('web')->guest());
    }
}
