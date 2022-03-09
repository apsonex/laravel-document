<?php

namespace Apsonex\LaravelDocument\Contracts;

interface InteractsWithDocument
{
    public function storagePathPrefix(): string;
}