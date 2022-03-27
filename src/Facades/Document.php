<?php

namespace Apsonex\LaravelDocument\Facades;

use Apsonex\LaravelDocument\Models\Document as DocumentModel;
use Apsonex\LaravelDocument\Support\DocumentFactory;
use Apsonex\LaravelDocument\Support\ImageFactory;
use Apsonex\LaravelDocument\Support\PendingDocument\PendingDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;


/**
 * @method static DocumentModel persist(PendingDocument $pendingDocument, DocumentModel $documentToUpdate = null)
 * @method static bool deleteVariations(DocumentModel $document, $deleteEmptyDir = false)
 */
class Document extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'document';
    }

}