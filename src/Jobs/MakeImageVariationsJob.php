<?php

namespace Apsonex\LaravelDocument\Jobs;

use Apsonex\LaravelDocument\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use PhpParser\Comment\Doc;

class MakeImageVariationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected Document|Builder|Model $document;

    public function __construct(
        public int   $documentId,
        public array $variations
    )
    {
        //
    }

    public function handle(): void
    {
        $this->document = Document::query()->where('id', $this->documentId)->firstOrFail();

        \Apsonex\LaravelDocument\Facades\Document::makeVariations($this->document, $this->variations);
    }
}