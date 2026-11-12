<?php

namespace Tests\Unit;

use App\Services\GamificacionService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GamificacionRulesTest extends TestCase
{
    #[Test]
    public function security_quiz_is_required_only_for_the_first_user_registration(): void
    {
        self::assertTrue(GamificacionService::debeAprobarSeguridad('usuario', false, false));
        self::assertFalse(GamificacionService::debeAprobarSeguridad('usuario', true, false));
        self::assertFalse(GamificacionService::debeAprobarSeguridad('usuario', false, true));
        self::assertFalse(GamificacionService::debeAprobarSeguridad('organizador', false, false));
        self::assertFalse(GamificacionService::debeAprobarSeguridad('admin', false, false));
    }

    #[Test]
    public function organizer_certificate_is_additional_to_verification_and_only_blocks_first_publication(): void
    {
        self::assertTrue(GamificacionService::debeCertificarPrimeraPublicacion('organizador', true, false, false));
        self::assertFalse(GamificacionService::debeCertificarPrimeraPublicacion('organizador', false, false, false));
        self::assertFalse(GamificacionService::debeCertificarPrimeraPublicacion('organizador', true, true, false));
        self::assertFalse(GamificacionService::debeCertificarPrimeraPublicacion('organizador', true, false, true));
        self::assertFalse(GamificacionService::debeCertificarPrimeraPublicacion('usuario', true, false, false));
    }
}