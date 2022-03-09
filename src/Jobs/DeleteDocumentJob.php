<?php

namespace Apsonex\LaravelDocument\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Apsonex\LaravelDocument\Actions\DeleteDocumentsAction;

class DeleteDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array|int $ids,
    )
    {
        //
    }

    public function handle(): void
    {
        DeleteDocumentsAction::execute($this->ids);
    }
}