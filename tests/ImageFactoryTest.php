<?php

namespace Apsonex\LaravelDocument\Tests;

use Apsonex\LaravelDocument\Support\ImageFactory;
use Apsonex\LaravelDocument\Support\ParseVariations;
use Apsonex\Rets\Models\BaseObject;
use Apsonex\SaasUtils\Facades\DiskProvider;
use Illuminate\Support\Facades\File;

class ImageFactoryTest extends TestCase
{

    /** @test */
    public function it_can_parse_image_variations()
    {
        $data = ParseVariations::parse([
            'dimension:100x100,filename',
            'FACEBOOK',
        ])->toArray();

        $this->assertEquals(100, $data['filename']['width']);
        $this->assertEquals(100, $data['filename']['height']);

        $this->assertEquals(1200, $data['facebook']['width']);
        $this->assertEquals(675, $data['facebook']['height']);
    }

    /** @test */
    public function it_upload_rets_object_image_to_disk()
    {
        /** @var BaseObject $baseObject */
        $baseObject = mls_data()->testImagesResponse(1)->first();

        $variations = [
            'dimension:100x100,filename',
            'FACEBOOK',
        ];

        $data = ImageFactory::forRetsBaseObject($baseObject)
            ->variations($variations)
            ->visibilityPublic()
            ->disk(DiskProvider::public())
            ->directory('some-dir')
            ->basename('image-name')
            ->persist();

        $this->assertSame(100, $data['variations']['filename']['width']);
        $this->assertSame(100, $data['variations']['filename']['height']);

        $this->assertSame(1200, $data['variations']['facebook']['width']);
        $this->assertSame(675, $data['variations']['facebook']['height']);

        $this->cleanStorage();
    }

    /** @test */
    public function it_upload_file_image_to_disk()
    {
        $this->cleanStorage();

        $variations = [
            'dimension:100x100,filename',
            'FACEBOOK',
        ];

        $data = ImageFactory::make($this->testFile('food-hd-long.jpg'))
            ->variations($variations)
            ->visibilityPublic()
            ->disk(DiskProvider::public())
            ->directory('some')
            ->basename('image-name')
            ->persist();

        $this->assertSame(100, $data['variations']['filename']['width']);
        $this->assertSame(100, $data['variations']['filename']['height']);

        $this->assertSame(1200, $data['variations']['facebook']['width']);
        $this->assertSame(675, $data['variations']['facebook']['height']);

        $this->cleanStorage();
    }

    /** @test */
    public function it_upload_string_image_to_disk()
    {
        $this->cleanStorage();

        $variations = [
            'dimension:100x100,filename',
            'FACEBOOK',
        ];

        $data = ImageFactory::make($this->testFile('food-hd-long.jpg')->getContent())
            ->variations($variations)
            ->visibilityPublic()
            ->disk(DiskProvider::public())
            ->directory('some')
            ->basename('image-name')
            ->persist();

        $this->assertSame(100, $data['variations']['filename']['width']);
        $this->assertSame(100, $data['variations']['filename']['height']);

        $this->assertSame(1200, $data['variations']['facebook']['width']);
        $this->assertSame(675, $data['variations']['facebook']['height']);

        $this->cleanStorage();
    }

    /** @test */
    public function it_remove_the_file_from_storage()
    {
        $this->cleanStorage();

        File::ensureDirectoryExists($this->getPublicStoragePath());

        $this->assertCount(0, File::allFiles($this->getPublicStoragePath()));

        $variations = [
            'dimension:100x100,filename',
            'FACEBOOK',
        ];

        $data = ImageFactory::make($this->testFile('food-hd-long.jpg')->getContent())
            ->variations($variations)
            ->visibilityPublic()
            ->disk(DiskProvider::public())
            ->directory('some')
            ->basename('image-name')
            ->persist();

        $this->assertCount(3, File::allFiles($this->getPublicStoragePath()));

        ImageFactory::deleteVariations(DiskProvider::public(), $data['variations'], true);

        $this->assertFalse(File::isDirectory($this->getPublicStoragePath() . '/some'));

        $this->assertCount(0, File::allFiles($this->getPublicStoragePath()));

        $this->cleanStorage();
    }

}