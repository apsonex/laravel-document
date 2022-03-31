<?php

namespace Apsonex\LaravelDocument\Tests;

use Apsonex\LaravelDocument\Actions\ProcessImagePendingDocumentAction;
use Apsonex\LaravelDocument\Support\PendingDocument\PendingDocument;
use Apsonex\Rets\Models\BaseObject;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProcessImagePendingDocumentActionTest extends TestCase
{

    use RefreshDatabase;

    /** @test */
    public function it_can_persist_rets_images_to_the_storage()
    {
        $images = mls_data()->testImagesResponse(1);

        /** @var BaseObject $baseObject */
        $baseObject = $images->first();

        $pending = PendingDocument::make()
            //->parentModel($this->listing)
            ->imageSourceRets($images->first());
            //->visibilityPublic()
            //->setDisk(DiskProvider::public())
            //->groupName($this->groupId)
            //->setDirectory($this->listing->media_path);

//
//        ProcessImagePendingDocumentAction::execute($pendingDocument);
    }


}