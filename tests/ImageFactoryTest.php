<?php

namespace Apsonex\Document\Tests;

use Apsonex\Document\Support\ImageFactory;
use Apsonex\Document\Support\ImageSizes;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageFactoryTest extends TestCase
{

    /** @test */
    public function it_upload_image_to_disk()
    {
        $file = $this->testFile('test.jpg');

        $path = vsprintf("%s/%s", [
            md5(Str::uuid()),
            md5(Str::uuid()) . '.jpg',
        ]);

        ImageFactory::make($file)
            ->encode($file->getClientOriginalExtension())
            ->save($path);

        $this->assertNotNull(Storage::get('public/' . $path));

        $this->cleanStorage();
    }

    /** @test */
    public function it_remove_the_file_from_storage()
    {
        $file = $this->testFile('test.jpg');

        $path = vsprintf("%s/%s", [
            md5(Str::uuid()),
            md5(Str::uuid()) . '.jpg',
        ]);

        ImageFactory::make($file)
            ->encode($file->getClientOriginalExtension())
            ->save($path);

        $this->assertNotNull(Storage::get('public/' . $path));

        ImageFactory::delete($path);

        $this->assertNull(Storage::get($path));

        $this->cleanStorage();
    }

    /** @test */
    public function it_can_store_variations_to_the_disk()
    {
        $this->cleanStorage();

        $file = $this->testFile('food-hd.jpg');

        $prefix = md5(Str::uuid());

        $path = vsprintf("%s/%s", [$prefix, md5(Str::uuid()) . '.jpg']);

        $factory = ImageFactory::make($file);

        $variations = [
            'facebook',
            'twitter',
            'thumbnail',
            'dimension:100x100,name'
        ];

        $document = $factory->saveWithVariations($path, $variations);

        $this->assertCount(count($variations), $document['variations']);

        $files = \Illuminate\Support\Facades\File::allFiles(Storage::path('public/' . $prefix));

        $this->assertCount(5, $files);

        $this->cleanStorage();
    }

}