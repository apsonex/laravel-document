<?php

namespace Apsonex\LaravelDocument\Support\ImageDrivers;

use Symfony\Component\Mime\MimeTypes;

trait ImageDriverUtils
{
    public function backup(): static
    {
        $this->imageManager->backup();
        return $this;
    }

    public function reset(): static
    {
        $this->imageManager->reset();
        return $this;
    }

    public function content(): string
    {
        return $this->object->getContent();
    }

    public function extension(): string
    {
        return app(MimeTypes::class)->getExtensions($this->imageManager->mime())[0] ?? 'jpg';
    }

    public function mime(): string
    {
        return $this->imageManager->mime();
    }

    public function getImageManager(): \Intervention\Image\Image
    {
        return $this->imageManager;
    }

}