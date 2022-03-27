<?php

namespace Apsonex\LaravelDocument\Tests;

use Apsonex\LaravelDocument\Support\PendingDocument\PendingDocument;
use Apsonex\LaravelDocument\Tests\fixtures\TestModel;
use Apsonex\Mls\Models\Listing;
use Apsonex\SaasUtils\Facades\DiskProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use League\Flysystem\Filesystem;

class DocumentModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function make_sure_database_exist()
    {
        $this->assertTrue(Schema::hasTable('documents'));
    }

    /** @test */
    public function it_upload_image_to_database_and_to_storage_with_variations()
    {
        $document = $this->createDocument();

        $this->assertEquals(1, $document->documentable_id);
    }

    /** @test */
    public function it_can_update_image_document()
    {
        $this->cleanStorage();

        $document = $this->createDocument($path = 'random-path');

        $previousData = $document->toArray();

        $path = storage_path('app/public' . '/' . $path);

        $this->assertCount(2, File::allFiles($path));

        /** @var Filesystem $filesystem */
        $filesystem = $document->diskInstance();

        foreach ($previousData['variations'] as $variation) {
            $this->assertTrue($filesystem->fileExists($variation['path']));
        }

        $variations = [
            'twitter',
            'dimension:100x100,name'
        ];

        $pendingDoc = (new PendingDocument)
            ->imageSource($this->testFile('food-hd.jpg'))
            ->basename('new-name')
            ->setVariations($variations)
            ->setDirectory($document->media_path)
            ->visibilityPublic()
            ->disk($document->diskInstance());

        $document = \Apsonex\LaravelDocument\Facades\Document::persist($pendingDoc, $document);

        dd($document->toArray());

        foreach ($previousData['variations'] as $variation) {
            $this->assertFalse($filesystem->fileExists($variation['path']));
        }

        foreach ($document->variations as $variation) {
            $this->assertTrue($filesystem->fileExists($variation['path']));
        }

        $this->cleanStorage();
    }

    /** @test */
    public function it_can_delete_documents_from_database_and_storage()
    {
        $this->cleanStorage();

        $document = $this->createDocument($path = 'random-path');

        $path = storage_path('app/public' . '/' . $path);

        $this->assertCount(2, File::allFiles($path));

        $document->delete();

        $this->assertFalse(File::isDirectory($path));
    }


    protected function createDocument($path = null, $model = null): \Apsonex\LaravelDocument\Models\Document
    {
        $this->cleanStorage();

        $model = (new TestModel())->forceFill([
            'id'         => 1,
            'media_path' => md5(Str::uuid()->toString()),
        ]);

        $variations = [
            'facebook',
            'dimension:100x100,thumbnail'
        ];

        $listing = (new Listing())->forceFill([
            'id'         => 1,
            'media_path' => $path ?: 'listings/media-path',
        ]);

        $pendingDoc = (new PendingDocument)
            ->imageSource($this->testFile('food-hd.jpg'))
            ->basename('gurinder')
            ->parentModel($listing)
            ->withoutOriginal()
            ->setVariations($variations)
            ->setAddedBy(auth()->id())
            ->visibilityPublic()
            ->disk(DiskProvider::public());

        return \Apsonex\LaravelDocument\Facades\Document::persist($pendingDoc);
    }

}