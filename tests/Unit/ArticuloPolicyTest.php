<?php

namespace Tests\Unit;

use App\Models\Articulo;
use App\Models\User;
use App\Policies\ArticuloPolicy;
use Tests\TestCase;

class ArticuloPolicyTest extends TestCase
{
    public function test_unverified_organizer_can_create_and_submit_only_owned_drafts_or_rejected_articles(): void
    {
        $organizador = new User(['rol' => 'organizador', 'organizadorVerificado' => false]);
        $organizador->setAttribute('_id', '507f1f77bcf86cd799439011');
        $propio = new Articulo([
            'autorId' => '507f1f77bcf86cd799439011',
            'estado' => 'borrador',
        ]);
        $ajeno = new Articulo([
            'autorId' => '507f1f77bcf86cd799439012',
            'estado' => 'borrador',
        ]);
        $policy = new ArticuloPolicy;

        $this->assertTrue($policy->create($organizador));
        $this->assertTrue($policy->update($organizador, $propio));
        $this->assertTrue($policy->enviarRevision($organizador, $propio));
        $this->assertFalse($policy->update($organizador, $ajeno));
        $this->assertFalse($policy->enviarRevision($organizador, $ajeno));
    }

    public function test_article_review_and_publication_transitions_require_admin(): void
    {
        $admin = new User(['rol' => 'admin']);
        $organizador = new User(['rol' => 'organizador']);
        $pendiente = new Articulo(['estado' => 'pendiente']);
        $publicado = new Articulo(['estado' => 'publicado']);
        $borrador = new Articulo(['estado' => 'borrador']);
        $policy = new ArticuloPolicy;

        $this->assertTrue($policy->aprobar($admin, $pendiente));
        $this->assertTrue($policy->rechazar($admin, $pendiente));
        $this->assertTrue($policy->despublicar($admin, $publicado));
        $this->assertTrue($policy->eliminar($admin, $borrador));
        $this->assertFalse($policy->aprobar($organizador, $pendiente));
        $this->assertFalse($policy->despublicar($admin, $pendiente));
    }
}
