<?php

namespace Apsonex\LaravelDocument\Tests\fixtures;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{

    public int $id = 1;


    public function storagePathPrefix(): string
    {
        return 'listings/' . $this->id;
    }

}