<?php

namespace Apsonex\LaravelDocument\Actions;

use Apsonex\LaravelDocument\Models\Document;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class DeleteDocumentsAction
{

    public static function execute(array|int $ids)
    {
        $self = new static();

        /** @var Collection $docs */
        $docs = Document::query()->whereIn('id', Arr::wrap($ids))->get();

        /**
         * We update status, so that we can figure out which one is not deleted upon command
         */
        $docs->toQuery()->update([
            'status' => Document::TO_BE_DELETED
        ]);

        $docs->each(function (Document $doc) use ($self) {
            $self->deleteDoc($doc);
        });
    }

    protected function deleteDoc(Document $doc)
    {
        /**
         * First we remove all the variations from storage
         */
        foreach ($doc->variations ?: [] as $name => $variation) {
            Storage::disk(
                $variation['visibility'] === 'public' ? 'public' : 'private'
            )->delete($variation['path']);
        }

        /**
         * Remove Original image itself
         */
        Storage::disk(
            $doc->visibility === 'public' ? 'public' : 'private'
        )->delete($doc->path);


        /**
         * Delete Doc
         */
        $doc->delete();
    }

}