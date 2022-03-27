<?php

namespace Apsonex\LaravelDocument\Support\ImageDrivers;

use Apsonex\Rets\Models\BaseObject;
use Illuminate\Filesystem\FilesystemAdapter;
use Intervention\Image\ImageManager;
use Symfony\Component\Mime\MimeTypes;

/**
 * $retsObject->getContentType() // return "image/jpeg"
 * $retsObject->getObjectId() // return "1"
 * $retsObject->getContentId() // return "MLS#"
 */
class RetsBaseObjectImageDriver implements ImageDriver
{
    use ImageDriverUtils;

    protected \Intervention\Image\Image $imageManager;

    protected FilesystemAdapter $storageDisk;

    public function __construct(protected BaseObject $object)
    {
        $this->imageManager = (new ImageManager(['driver' => 'imagick']))->make($this->object->getContent());
    }

    public function id(): string
    {
        return $this->object->getObjectId();
    }

    public function filename(): string
    {
        return $this->object->getObjectId();
    }

    public function mime(): string
    {
        return $this->object->getContentType();
    }

    public function mlsNumber(): string
    {
        return $this->object->getContentId();
    }
}