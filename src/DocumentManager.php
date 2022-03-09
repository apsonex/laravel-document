<?php

namespace Apsonex\LaravelDocument;

use Apsonex\LaravelDocument\Jobs\DeleteDocumentJob;
use Apsonex\LaravelDocument\Models\Document as DocumentModel;
use Apsonex\LaravelDocument\Support\DocumentFactory;
use Apsonex\LaravelDocument\Support\ImageFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class DocumentManager
{

    public function saveImageFor($model, UploadedFile $file, $public = true, $variations = []): DocumentModel
    {
        return DocumentFactory::saveImageFor($model, $file, $public, $variations);
    }

    public function imageFactory(UploadedFile|string $file): ImageFactory
    {
        return ImageFactory::make($file);
    }

    public static function delete(DocumentModel|int $document): bool
    {
        $document = is_object($document) ? $document : DocumentManager::whereId($document)->firstOrFail();

        return ImageFactory::delete($document->path, $document->visibility);
    }

    public static function deleteById(array|int $ids): void
    {
        if ($ids) {
            DeleteDocumentJob::dispatch(Arr::wrap($ids));
        }
    }

}