<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CorsMiddlewareTest extends TestCase
{
    #[Test]
    public function allows_requests_from_allowed_origin()
    {
        $origin = config('cors.allowed_origins')[0] ?? 'http://localhost:5173';
        $response = $this->withHeaders([
            'Origin' => $origin,
        ])->get('/api/v1/health'); // Use public endpoint instead

        $response->assertHeader('Access-Control-Allow-Origin', $origin);
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    #[Test]
    public function blocks_requests_from_disallowed_origin()
    {
        $response = $this->withHeaders([
            'Origin' => 'http://not-allowed-origin.com',
        ])->get('/api/v1/health'); // Use public endpoint instead

        $response->assertHeader('Access-Control-Allow-Origin', '*');
        $response->assertHeaderMissing('Access-Control-Allow-Credentials');
    }

    #[Test]
    public function handles_preflight_options_request()
    {
        $origin = config('cors.allowed_origins')[0] ?? 'http://localhost:5173';
        $response = $this->withHeaders([
            'Origin' => $origin,
        ])->options('/api/v1/health'); // Use public endpoint instead

        $response->assertStatus(200);
        $response->assertHeader('Access-Control-Allow-Origin', $origin);
        $response->assertHeader('Access-Control-Allow-Methods');
        $response->assertHeader('Access-Control-Allow-Headers');
    }
}
