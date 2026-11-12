<?php

namespace Tests\Unit;

use App\Http\Middleware\ThrottleAttendance;
use Illuminate\Http\Request;
use Tests\TestCase;

class ThrottleAttendanceTest extends TestCase
{
    public function test_attendance_rate_limiter_uses_file_cache_independently_of_default_store(): void
    {
        $request = Request::create('/api/asistencias', 'POST');
        $request->setUserResolver(fn () => new class
        {
            public function getAuthIdentifier(): string
            {
                return bin2hex(random_bytes(12));
            }
        });

        $response = app(ThrottleAttendance::class)->handle(
            $request,
            fn () => response()->noContent(),
        );

        $this->assertSame(204, $response->getStatusCode());
    }
}