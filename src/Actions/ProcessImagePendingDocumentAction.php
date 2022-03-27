<?php

namespace Apsonex\LaravelDocument\Actions;

use Apsonex\LaravelDocument\Models\Document;
use Apsonex\LaravelDocument\Support\ImageFactory;
use Apsonex\LaravelDocument\Support\PendingDocument\PendingDocument;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessImagePendingDocumentAction
{

    public function __construct(
        protected PendingDocument $pendingDocument,
        protected ?Document       $document
    )
    {
        //
    }

    public static function execute(PendingDocument $pendingDocument, $documentToUpdate = null): Document
    {
        $self = new static($pendingDocument, $documentToUpdate);

        return $self->persist();
    }

    protected function persist(): Document
    {
        $imageFactory = $this->makeImageFactory();

        if ($this->pendingDocument->public === true) {
            $imageFactory->visibilityPublic();
        }

        if ($this->pendingDocument->withOriginal === false) {
            $imageFactory->withoutOriginal();
        }

        $imageFactory->variations($this->pendingDocument->variations);

        return $this->document ? $this->updateDocument($imageFactory) : $this->createDocument($imageFactory);
    }

    protected function updateDocument(ImageFactory $factory): Document
    {
        $previousData = $this->pendingDocument->deletePreviousImages ? $this->document->toArray() : null;

        $factory->disk($this->pendingDocument->disk ?: $this->document->diskInstance());

        if ($basename = $this->pendingDocument->basename) {
            $factory->basename($basename);
        }

        $data = $factory
            ->directory($this->pendingDocument->directory)
            ->persist();

        $this->document->fill([
            ...$data,
            'added_by' => $this->getAddedBy(),
            'type'     => $this->pendingDocument->type ?: $this->document->type,
            'group'    => $this->pendingDocument->group ?: $this->document->group,
            'status'   => $this->pendingDocument->status ?: $this->document->status,
        ])->save();

        if ($this->pendingDocument->deletePreviousImages) {
            ImageFactory::deleteVariations($this->document->diskInstance(), $previousData['variations'], true);
        }

        return $this->document;
    }

    protected function createDocument(ImageFactory $factory): Document
    {
        $data = $factory
            ->disk($this->pendingDocument->disk)
            ->basename($this->pendingDocument->basename)
            ->directory($this->pendingDocument->directory)
            ->persist();

        $this->document = new Document();

        $data = [
            'documentable_type' => get_class($this->pendingDocument->model),
            'documentable_id'   => $this->pendingDocument->model->id,
            'type'              => $this->pendingDocument->type,
            'added_by'          => $this->pendingDocument->addedBy ?: (auth()->check() ? auth()->id() : null),
            'group'             => $this->pendingDocument->group,
            'status'            => $this->pendingDocument->status,
            ...$data,
        ];

        return Document::create($data);
    }

    protected function makeImageFactory(): ImageFactory
    {
        try {
            return match ($this->pendingDocument->srcType) {
                'rets' => ImageFactory::forRetsBaseObject($this->pendingDocument->imageSrc),
                default => ImageFactory::make($this->pendingDocument->imageSrc)
            };
        } catch (\Intervention\Image\Exception\NotReadableException $e) {
            $strings = [
                "Image not readable at \Apsonex\LaravelDocument\Actions\ProcessImagePendingDocumentAction::class",
            ];

            if($this->document?->id) {
                $strings[] = "Document: " . get_class($this->document) . ", ID: " . $this->document->id;
            }

            Log::alert(implode('. ', $strings));
        }
    }

    protected function getAddedBy(): ?int
    {
        if ($this->pendingDocument->addedBy) {
            return $this->pendingDocument->addedBy;
        }

        return $this->document ?
            $this->document->added_by :
            (auth()->check() ? auth()->id() : null);
    }
}