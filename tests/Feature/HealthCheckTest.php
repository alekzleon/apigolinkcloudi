<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_check_returns_application_status(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.application', 'Cloudi Go')
            ->assertJsonPath('data.database', 'connected')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'status',
                    'application',
                    'database',
                    'timestamp',
                ],
            ]);
    }
}
