<?php

namespace Apsonex\Document;

use Apsonex\Document\Models\Document as DocumentModel;
use Apsonex\Document\Support\DocumentFactory;
use Apsonex\Document\Support\ImageFactory;
use Illuminate\Http\UploadedFile;

class Document
{

    public function saveImageFor($model, UploadedFile|array $file, $public = true, $variations = []): DocumentModel
    {
        return DocumentFactory::saveImageFor($model, $file, $public, $variations);
    }

    public function imageFactory(UploadedFile|string $file): ImageFactory
    {
        return ImageFactory::make($file);
    }

    public static function delete(DocumentModel|int $document): bool
    {
        $document = is_object($document) ? $document : Document::whereId($document)->firstOrFail();

        return ImageFactory::delete($document->path, $document->visibility);
    }

}