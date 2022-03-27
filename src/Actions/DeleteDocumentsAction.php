<?php

namespace Apsonex\LaravelDocument\Actions;

use Illuminate\Support\Arr;
use Apsonex\LaravelDocument\Models\Document;
use Illuminate\Database\Eloquent\Collection;

class DeleteDocumentsAction
{

    public static function execute(array|int $ids)
    {
        /** @var Collection $docs */
        $documents = Document::query()->whereIn('id', Arr::wrap($ids))->get();

        /**
         * We update status
         * So that we can figure out which one is not deleted upon command
         */
        $documents->toQuery()->update([
            'status' => Document::TO_BE_DELETED
        ]);

        $documents->each(fn(Document $doc) => $doc->delete());
    }
}