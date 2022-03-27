<?php

namespace Apsonex\LaravelDocument\Tests;

use Apsonex\LaravelDocument\Support\PendingDocument\PendingDocument;
use Apsonex\Mls\MLS;
use Apsonex\Mls\Models\Listing;
use Apsonex\SaasUtils\Facades\DiskProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class RetsImageDocumentCreateTest extends TestCase
{

    use RefreshDatabase;

    /**
     * @test
     * $retsObject->getContentType() // return "image/jpeg"
     * $retsObject->getObjectId() // return "1"
     * $retsObject->getContentId() // return "MLS#"
     */
    public function it_process_image_source_from_rets_object()
    {
        $listing = (new Listing())->forceFill(['id' => 1, 'media_path' => 'listings/' . md5(Str::uuid())]);

        $mls = mls()->loadCredentials(env('TRREB_VOWS_USERNAME'), env('TRREB_VOWS_PASSWORD'), env('TRREB_VOWS_LOGIN_URL'));

        $this->assertTrue($mls->isValidConnection());

        $images = $mls->fetchImages('W5552175');

        /** @var \Apsonex\Rets\Models\BaseObject $first */
        $first = $images->first();

        $po = PendingDocument::make()
            ->parentModel($listing)
            ->setDirectory($listing->media_path . '/images')
            ->imageSourceRets($first)
            ->setAddedBy(1)
            ->visibilityPublic()
            ->disk(DiskProvider::public());

        $doc = \Apsonex\LaravelDocument\Facades\Document::persist($po);

        $this->assertIsString($doc->variations['original']['path']);
    }

}