<?php

namespace Apsonex\LaravelDocument\Support;

use Apsonex\LaravelDocument\Models\Document;
use Apsonex\LaravelDocument\Support\PendingDocument\PendingDocument;
use Apsonex\LaravelDocument\Actions\ProcessImagePendingDocumentAction;

class DocumentFactory
{

    public static function make(): static
    {
        return new static();
    }


    public function persist(PendingDocument $pendingDocument, Document $documentToUpdate = null): ?Document
    {
        if (isset($pendingDocument->type) && $pendingDocument->type === 'image') {
            return ProcessImagePendingDocumentAction::execute($pendingDocument, $documentToUpdate);
        }

        return null;
    }

    public function delete(Document $document, $deleteEmptyDir = false)
    {
        if ($document->type === 'image') {
            ImageFactory::deleteVariations($document->diskInstance(), $document->variations, $deleteEmptyDir);
        }
    }
}
