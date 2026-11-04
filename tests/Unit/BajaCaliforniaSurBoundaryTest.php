<?php

namespace Tests\Unit;

use App\Services\BajaCaliforniaSurBoundary;
use Tests\TestCase;

class BajaCaliforniaSurBoundaryTest extends TestCase
{
    public function test_points_inside_baja_california_sur_are_accepted(): void
    {
        $boundary = new BajaCaliforniaSurBoundary;

        self::assertTrue($boundary->contains(24.1426, -110.3128));
        self::assertTrue($boundary->contains(22.8905, -109.9167));
        self::assertTrue($boundary->contains(27.958, -114.06));
    }

    public function test_points_outside_baja_california_sur_are_rejected(): void
    {
        $boundary = new BajaCaliforniaSurBoundary;

        self::assertFalse($boundary->contains(32.5149, -117.0382));
        self::assertFalse($boundary->contains(19.4326, -99.1332));
        self::assertFalse($boundary->contains(24.1426, -109.5));
        self::assertFalse($boundary->contains('not-a-latitude', -110.3));
    }
}
