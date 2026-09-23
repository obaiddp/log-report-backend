<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_health_endpoint_reports_the_api_as_ready(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'service' => 'Log Report API',
            ]);
    }

    public function test_user_endpoint_returns_401_without_authentication(): void
    {
        $response = $this->getJson('/api/v1/user');

        $response->assertUnauthorized();
    }
}
