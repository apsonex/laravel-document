<?php

namespace Apsonex\LaravelDocument;

use Apsonex\LaravelDocument\Jobs\MakeImageVariationsJob;
use Apsonex\LaravelDocument\Models\Document;
use Apsonex\LaravelDocument\Support\DocumentFactory;
use Apsonex\LaravelDocument\Support\ImageFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class DocumentManager
{

    protected bool $queue = false;

    protected DocumentFactory $factory;

    public function __construct(DocumentFactory $factor)
    {
        $this->factory = $factor;
    }

    public function queue(): static
    {
        $this->queue = true;

        return $this;
    }

    public function saveImageFor($model, UploadedFile $file, $public = true, $variations = []): Document
    {
        return $this->factory
            ->queue($this->queue)
            ->saveImageFor($model, $file, $public, $variations);
    }

    public function makeVariations(Document $document, $variations = [])
    {
        $document->update([
            'variations' => $this->factory->makeVariations($document, $variations)
        ]);
    }

    public function imageFactory(UploadedFile|string $file): ImageFactory
    {
        return ImageFactory::make($file);
    }

    public static function delete(Document|int $document): bool
    {
        return Storage::disk($document->diskName())->delete($document->path);
    }
}