<?php

namespace Apsonex\Document\Facades;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;


// @method static \Illuminate\Contracts\Filesystem\Filesystem assertExists(string|array $path)

/**
 * @method static \Apsonex\Document\Models\Document saveImageFor($model, UploadedFile|array $file, bool $public, array $variations)
 * @method static \Apsonex\Document\Support\ImageFactory imageFactory(UploadedFile|string $file)
 * @method static bool delete(\Apsonex\Document\Models\Document|int $document)
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