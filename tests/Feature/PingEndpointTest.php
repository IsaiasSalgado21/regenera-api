<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PingEndpointTest extends TestCase
{
    public function test_ping_returns_ok_when_mongodb_is_available(): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('command')
            ->once()
            ->with(['ping' => 1]);

        DB::shouldReceive('connection')
            ->once()
            ->with('mongodb')
            ->andReturn($connection);

        $this->getJson('/api/ping')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }

    public function test_ping_logs_mongodb_errors_without_exposing_them(): void
    {
        $exception = new RuntimeException('Private MongoDB connection detail.');

        DB::shouldReceive('connection')
            ->once()
            ->with('mongodb')
            ->andThrow($exception);

        Log::shouldReceive('error')
            ->once()
            ->with('MongoDB health check failed.', ['exception' => $exception]);

        $this->getJson('/api/ping')
            ->assertStatus(500)
            ->assertExactJson([
                'status' => 'error',
                'message' => 'Servicio no disponible',
            ])
            ->assertDontSee('Private MongoDB connection detail.');
    }
}
