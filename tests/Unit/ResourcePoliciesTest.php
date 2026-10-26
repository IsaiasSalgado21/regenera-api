<?php

namespace Tests\Unit;

use App\Models\FloraFauna;
use App\Models\Lugar;
use App\Models\User;
use App\Policies\FloraFaunaPolicy;
use App\Policies\LugarPolicy;
use MongoDB\BSON\ObjectId;
use Tests\TestCase;

class ResourcePoliciesTest extends TestCase
{
    public function test_unverified_organizer_can_create_and_edit_only_owned_records(): void
    {
        $organizador = new User(['rol' => 'organizador', 'organizadorVerificado' => false]);
        $organizador->setAttribute('_id', '507f1f77bcf86cd799439011');
        $otroOrganizador = new User(['rol' => 'organizador', 'organizadorVerificado' => false]);
        $otroOrganizador->setAttribute('_id', '507f1f77bcf86cd799439012');

        $lugarPropio = new Lugar(['estado' => 'activo', 'creadoPor' => new ObjectId('507f1f77bcf86cd799439011')]);
        $registroAjeno = new FloraFauna(['estado' => 'publicado', 'creadoPor' => new ObjectId('507f1f77bcf86cd799439012')]);
        $lugarPolicy = new LugarPolicy;
        $floraFaunaPolicy = new FloraFaunaPolicy;

        $this->assertTrue($lugarPolicy->create($organizador));
        $this->assertTrue($lugarPolicy->update($organizador, $lugarPropio));
        $this->assertFalse($floraFaunaPolicy->update($organizador, $registroAjeno));
        $this->assertFalse($floraFaunaPolicy->archivar($organizador, $registroAjeno));
    }
}
