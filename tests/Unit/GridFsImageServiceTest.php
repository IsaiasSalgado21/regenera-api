<?php

namespace Tests\Unit;

use App\Services\GridFsImageService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GridFsImageServiceTest extends TestCase
{
    public function test_removing_an_image_keeps_the_other_existing_images(): void
    {
        $service = new GridFsImageService;

        [$images, $removed] = $service->applyCollectionChanges(
            ['first.jpg', 'second.jpg'],
            [],
            [0],
            10,
            'articulos',
            'artículo',
        );

        $this->assertSame(['second.jpg'], $images);
        $this->assertSame(['first.jpg'], $removed);
    }

    public function test_existing_and_new_images_cannot_exceed_the_collection_limit(): void
    {
        $service = new GridFsImageService;

        $this->expectException(ValidationException::class);
        $service->applyCollectionChanges(
            ['one.jpg', 'two.jpg'],
            [new \Illuminate\Http\UploadedFile(__FILE__, 'new.jpg', 'image/jpeg', null, true)],
            [],
            2,
            'articulos',
            'artículo',
        );
    }

    public function test_legacy_single_images_are_normalized_for_editing(): void
    {
        $service = new GridFsImageService;

        $this->assertSame(['legacy.jpg'], $service->paths([], 'legacy.jpg'));
        $this->assertSame(['legacy.jpg'], $service->paths('legacy.jpg'));
    }
}
