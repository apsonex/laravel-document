<?php

namespace Apsonex\Document\Tests;

use Apsonex\Document\Document;

class DocumentBindingTest extends TestCase
{

    /** @test */
    public function it_bind_document_to_container()
    {
        $this->assertInstanceOf(Document::class, document());
    }
    
}