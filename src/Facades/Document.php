<?php

namespace Apsonex\LaravelDocument\Facades;

use Apsonex\LaravelDocument\DocumentManager;
use Apsonex\LaravelDocument\Models\Document as DocumentModel;
use Apsonex\LaravelDocument\Support\ImageFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;


/**
 * @method static DocumentManager queue()
 * @method static DocumentModel saveImageFor($model, UploadedFile $file, bool $public, array $variations)
 * @method static ImageFactory imageFactory(UploadedFile|string $file)
 * @method static bool delete(DocumentModel|int $document)
 * @method static void deleteById(array|int $ids)
 * @method static void makeVariations(\Apsonex\LaravelDocument\Models\Document $ids, array $variations)
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