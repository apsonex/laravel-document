<?php

namespace Apsonex\LaravelDocument\Tests;

use Apsonex\LaravelDocument\Actions\DeleteDocumentsAction;
use Apsonex\LaravelDocument\Models\Document;
use Apsonex\LaravelDocument\Support\DocumentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DocumentModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function make_sure_database_exist()
    {
        $this->assertTrue(Schema::hasTable('documents'));
    }

    /** @test */
    public function it_persist_images_to_database()
    {
        $data = [
            'order'             => 0,
            'documentable_type' => '\App\Model\Random',
            'documentable_id'   => 1,
            'type'              => 'image',
            'mime'              => 'image/jpg',
            'name'              => 'FileName',
            'file_name'         => 'FileName.jpeg',
            'disk'              => 'public',
            'path'              => 'image/path',
            'size'              => 12345,
            'visibility'        => 'public',
            'variations'        => [],
        ];
        $this->assertDatabaseCount(Document::class, 0);

        $document = Document::create($data);

        $this->assertIsArray($document->variations);

        $this->assertDatabaseCount(Document::class, 1);
    }


    /** @test */
    public function it_upload_image_to_database_and_to_storage_with_variations()
    {
        $this->cleanStorage();

        $model = (new \stdClass());

        $model->id = 1;

        $model->media_path = md5(Str::uuid()->toString());

        $variations = [
            'facebook',
            'twitter',
            'thumbnail',
            'dimension:100x100,name'
        ];

        $document = DocumentFactory::saveImageFor($model, $this->testFile('food-hd.jpg'), true, $variations);

        $this->assertEquals(get_class($model), $document->documentable_type);

        $this->assertEquals($model->id, $document->documentable_id);
    }

    /** @test */
    public function it_can_delete_documents_from_database_and_storage()
    {
        $this->cleanStorage();

        $model = (new \stdClass());

        $model->id = 1;

        $model->media_path = md5(Str::uuid()->toString());

        $variations = [
            'facebook',
            'twitter',
            'thumbnail',
            'dimension:100x100,name'
        ];

        $document = DocumentFactory::saveImageFor($model, $this->testFile('food-hd.jpg'), true, $variations);

        $this->assertNotEmpty(File::allFiles(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/storage/app/public'));

        DeleteDocumentsAction::execute($document->id);

        $this->assertEmpty(File::allFiles(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/storage/app/public'));
    }

}