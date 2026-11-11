<?php

namespace Tests\Unit;

use App\Services\VoluntariadoCupoService;
use MongoDB\BSON\ObjectId;
use ReflectionClass;
use Tests\TestCase;

class VoluntariadoCupoServiceTest extends TestCase
{
    public function test_mongo_document_ids_are_converted_to_object_ids(): void
    {
        $service = new VoluntariadoCupoService;
        $method = (new ReflectionClass($service))->getMethod('mongoId');

        $this->assertInstanceOf(ObjectId::class, $method->invoke($service, '6af41f4404ac47d1250b288b'));
        $this->assertSame('legacy-id', $method->invoke($service, 'legacy-id'));
    }
}