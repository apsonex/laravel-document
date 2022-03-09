<?php

namespace Apsonex\Document;

use Apsonex\Document\Jobs\DeleteDocumentJob;
use Apsonex\Document\Models\Document as DocumentModel;
use Apsonex\Document\Support\DocumentFactory;
use Apsonex\Document\Support\ImageFactory;
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