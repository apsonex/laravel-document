<?php

namespace Apsonex\LaravelDocument\Tests;

use Apsonex\LaravelDocument\DocumentManager;

class DocumentBindingTest extends TestCase
{

    /** @test */
    public function it_bind_document_to_container()
    {
        $this->assertInstanceOf(DocumentManager::class, document());
    }

}