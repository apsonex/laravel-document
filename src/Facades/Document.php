<?php

namespace Apsonex\Document\Facades;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;


/**
 * @method static \Apsonex\Document\Models\Document saveImageFor($model, UploadedFile $file, bool $public, array $variations)
 * @method static \Apsonex\Document\Support\ImageFactory imageFactory(UploadedFile|string $file)
 * @method static bool delete(\Apsonex\Document\Models\Document|int $document)
 * @method static void deleteById(array|int $ids)
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