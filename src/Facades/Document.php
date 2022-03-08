<?php

namespace Apsonex\Document\Facades;

use Illuminate\Support\Facades\Facade;


// @method static \Illuminate\Contracts\Filesystem\Filesystem assertExists(string|array $path)
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