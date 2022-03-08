<?php

namespace Apsonex\Document\Tests;

use Apsonex\Document\DocumentManager;
use Apsonex\Document\Facades\Document;

class DocumentBindingTest extends TestCase
{

    /** @test */
    public function it_bind_document_to_container()
    {
        $this->assertInstanceOf(DocumentManager::class, document());
    }

}