<?php

namespace Apsonex\LaravelDocument\Support\ImageDrivers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

class UploadedFileImageDriver implements ImageDriver
{
    use ImageDriverUtils;

    protected \Intervention\Image\Image $imageManager;

    protected FilesystemAdapter $storageDisk;

    public function __construct(
        protected UploadedFile|string $object
    )
    {
        $this->imageManager = (new ImageManager(['driver' => 'imagick']))->make(
            $this->object
        );
    }

    public function id(): string
    {
        return md5(Str::uuid());
    }

    public function filename(): string
    {
        return str($this->imageManager->basename)->beforeLast('.')->toString();
    }
}