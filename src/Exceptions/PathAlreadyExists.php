<?php

namespace Apsonex\LaravelDocument\Exceptions;

class PathAlreadyExists extends \Exception
{
    public static function create(string $path): static
    {
        return new static("Path `{$path}` already exists.");
    }
}