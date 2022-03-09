<?php

namespace Apsonex\LaravelDocument\Tests;

use Apsonex\LaravelDocument\Facades\Document;
use Apsonex\LaravelDocument\Support\ImageVariationNames;
use Apsonex\LaravelDocument\Tests\fixtures\TestModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VariationsTest extends TestCase
{

    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->cleanStorage();
    }

    /** @test */
    public function it_queue_variation_processing()
    {
        $model = new TestModel();

        File::ensureDirectoryExists($this->getPublicStoragePath());

        $this->assertEmpty(File::allFiles($this->getPublicStoragePath()));

        $this->assertCount(0, $this->allJobs());

        $document = Document::queue()->saveImageFor($model, $this->testFile('food-hd.jpg'), true, [ImageVariationNames::FACEBOOK, ImageVariationNames::TWITTER]);

        $this->assertCount(1, File::allFiles(str($document->fullPath())->beforeLast('/')));

        $this->assertCount(1, $this->allJobs());
    }


    /** @test */
    public function it_ensure_job_processed_variations()
    {
        $this->cleanStorage();

        $model = $this->getTestModel();

        $variations = [ImageVariationNames::FACEBOOK, ImageVariationNames::TWITTER];

        $document = Document::queue()->saveImageFor($model, $this->testFile('food-hd.jpg'), true, $variations);

        $this->assertCount(1, File::allFiles(
            $dir = str($document->fullPath())->beforeLast('/')
        ));

        $this->assertCount(1, $this->allJobs());

        $this->processAllJobs();

        $this->assertCount(2, File::allFiles($document->variationsDirectory()));

        $this->cleanStorage();
    }

    protected function getTestModel(): \stdClass
    {
        $model = (new \stdClass());

        $model->id = 1;

        $model->media_path = 'one';

        return $model;
    }

}