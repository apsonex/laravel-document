<?php

namespace Apsonex\LaravelDocument\Support;

use Apsonex\LaravelDocument\Jobs\MakeImageVariationsJob;
use Apsonex\LaravelDocument\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DocumentFactory
{

    protected bool $queue = false;

    public static function make(): static
    {
        return new static();
    }

    public function queue($queue = true): static
    {
        $this->queue = $queue;

        return $this;
    }

    public function saveImageFor($model, UploadedFile $file, $public = true, $variations = []): Document
    {
        $imageFactory = $this->makeImageFactory($file);

        $public ? $imageFactory->public() : $imageFactory->private();

        $baseName = str($file->getClientOriginalName())->beforeLast('.')->slug()->toString();

        $pathPrefix = method_exists($model, 'storagePathPrefix') ? $model->storagePathPrefix() : md5(Str::uuid());

        //$path = vsprintf("%s/%s", [$model->media_path ?? md5(Str::uuid()), str($baseName . ' ' . now()->getTimestamp())->slug()->toString() . '.jpg']);
        $path = vsprintf("%s/%s", [
            $pathPrefix,
            $baseName . '-' . now()->getTimestamp() . '.jpg'
        ]);

        $data = [
            'documentable_type' => get_class($model),
            'documentable_id'   => $model->id,
            'type'              => 'image',
        ];

        if ($this->queue === true) {
            $document = $this->createDocument(
                array_merge($data, $imageFactory->save($path, $imageFactory->getImage()))
            );

            if (!empty($variations)) {
                $this->queueToMakeDocumentVariations($document->id, $variations);
            }

            return $document;
        }

        $data = array_merge($data, $imageFactory->saveWithVariations($path, $variations));

        return $this->createDocument(
            $data
        );
    }

    public function makeVariations(Document $document, $variations = []): array
    {
        $imageFactory = $this->makeImageFactory($document->fullPath());

        return $imageFactory->onlyVariations(
            str($document->path)->beforeLast('/'),
            $variations
        );
    }

    public function queueToMakeDocumentVariations(array|int $ids, $variations): void
    {
        foreach (Arr::wrap($ids) as $id) {
            MakeImageVariationsJob::dispatch($id, $variations);
        }
    }

    public function createDocument($data): Document
    {
        return Document::create($data);
    }

    public function makeImageFactory($file): ImageFactory
    {
        return ImageFactory::make($file);
    }

}
