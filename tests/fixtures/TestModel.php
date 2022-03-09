<?php

namespace Apsonex\LaravelDocument\Tests\fixtures;

class TestModel
{

    public int $id = 1;

    public function storagePathPrefix(): string
    {
        return 'listings/' . $this->id;
    }

}