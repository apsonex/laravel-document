<?php

namespace Apsonex\LaravelDocument\Contracts;

interface InteractsWithDocument
{
    public function storageDirectory(): string;
}