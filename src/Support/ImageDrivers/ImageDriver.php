<?php

namespace Apsonex\LaravelDocument\Support\ImageDrivers;

interface ImageDriver
{
    public function filename(): string;

    public function reset(): static;

    public function backup(): static;

    public function extension(): string;

    public function mime(): string;

    public function getImageManager(): \Intervention\Image\Image;
}