<?php

namespace Apsonex\LaravelDocument\Actions;

use Apsonex\LaravelDocument\Models\Document;
use Apsonex\LaravelDocument\Support\ImageFactory;
use Apsonex\LaravelDocument\Support\PendingDocument\PendingDocument;
use Apsonex\Rets\Models\BaseObject;
use Apsonex\SaasUtils\Facades\DiskProvider;
use Illuminate\Support\Facades\Log;

class ProcessImagePendingDocumentAction
{

    protected ImageFactory $factory;

    protected ?array $previousImagesCache;

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
        $this->configFactory();

        return $this->document ? $this->updateDocument() : $this->createDocument();
    }

    /**
     * Update Document
     */
    protected function updateDocument(): Document
    {
        $this->cachePreviousImages();

        $this->configureFactoryForUpdateAction();

        $this->document->fill([
            ...$this->factory->persist(),
            'added_by' => $this->getAddedBy(),
            'type'     => $this->pendingDocument->type ?: $this->document->type,
            'group'    => $this->pendingDocument->group ?: $this->document->group,
            'status'   => $this->pendingDocument->status ?: $this->document->status,
        ])->save();

        $this->deletePreviousImages();

        return $this->document;
    }

    /**
     * Create Document
     */
    protected function createDocument(): Document
    {
        $this->configFactoryForCreateAction();

        $data = [
            ...$this->factory->persist(),
            'documentable_type' => get_class($this->pendingDocument->model),
            'documentable_id'   => $this->pendingDocument->model->id,
            'type'              => $this->pendingDocument->type,
            'added_by'          => $this->pendingDocument->addedBy ?: (auth()->check() ? auth()->id() : null),
            'group'             => $this->pendingDocument->group,
            'status'            => $this->pendingDocument->status,
        ];

        return Document::create($data);
    }

    /**
     * Added By
     */
    protected function getAddedBy(): ?int
    {
        if ($this->pendingDocument->addedBy) {
            return $this->pendingDocument->addedBy;
        }

        return $this->document ?
            $this->document->added_by :
            (auth()->check() ? auth()->id() : null);
    }


    /**
     * Get rets imag error
     */
    protected function getsRetsError($object): bool|string
    {
        if ($object instanceof BaseObject && method_exists($object, 'isError') && $object->isError()) {
            return $object->getError()->getCode() . '|' . $object->getError()->getMessage();
        }

        return false;
    }

    /**
     * Configure Factory
     */
    protected function configFactory()
    {
        $this->factory = $this->makeImageFactory();

        if ($this->pendingDocument->public === true) {
            $this->factory->visibilityPublic();
        }

        if ($this->pendingDocument->withOriginal === false) {
            $this->factory->withoutOriginal();
        }

        $this->factory
            ->batchId($this->pendingDocument->batchId)
            ->variations($this->pendingDocument->variations);
    }

    /**
     * Configure factory for image create
     */
    protected function configFactoryForCreateAction()
    {
        $this->factory
            ->disk(DiskProvider::byVisibility($this->pendingDocument->public ? 'public' : 'private'))
            ->basename($this->pendingDocument->basename)
            ->directory($this->pendingDocument->directory);
    }

    /**
     * Make Image Factory
     */
    protected function makeImageFactory(): ?ImageFactory
    {
        try {
            return match ($this->pendingDocument->srcType) {
                'rets' => ImageFactory::forRetsBaseObject($this->pendingDocument->imageSrc),
                default => ImageFactory::make($this->pendingDocument->imageSrc)
            };
        } catch (\Intervention\Image\Exception\NotReadableException $e) {
            $strings = [
                "Image not readable at \Apsonex\LaravelDocument\Actions\ProcessImagePendingDocumentAction::class",
                "message:" . $e->getMessage(),
                "srcType:" . $this->pendingDocument->srcType
            ];

            if ($this->document?->id) {
                $strings[] = "Document: " . get_class($this->document) . ", ID: " . $this->document->id;
            }

            Log::alert(implode('. ', $strings));

            return null;
        }
    }

    /**
     * Cache previous images
     */
    protected function cachePreviousImages()
    {
        $this->previousImagesCache = $this->pendingDocument->deletePreviousImages ? $this->document->toArray() : null;
    }

    protected function configureFactoryForUpdateAction()
    {
        $this->factory->disk($this->document->diskInstance());

        if ($basename = $this->pendingDocument->basename) {
            $this->factory->basename($basename);
        }

        $this->factory->directory($this->pendingDocument->directory);
    }

    /**
     * Delete Previous Images
     */
    protected function deletePreviousImages()
    {
        if ($this->pendingDocument->deletePreviousImages) {
            ImageFactory::deleteVariations($this->document->diskInstance(), $this->previousImagesCache['variations'], true);
        }
    }
}