<?php

namespace Apsonex\LaravelDocument\Support\PendingDocument;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class PendingDocument
{
    public string $type;
    public string $srcType;
    public mixed $imageSrc;
    public Model $model;
    public ?string $directory = null;
    public ?string $basename = null;
    public ?string $group = null;
    public ?string $status = null;
    public array $variations = [];
    public ?Filesystem $disk = null;
    public bool $public = false;
    public bool $withOriginal = true;
    public bool $deletePreviousImages = true;
    public ?int $addedBy = null;


    public static function make(): static
    {
        return new static();
    }

    public function imageSourceRets($retsObject): static
    {
        $this->type = 'image';
        $this->srcType = 'rets';
        $this->imageSrc = $retsObject;
        return $this;
    }

    public function imageSource(UploadedFile|string $uploadedFile): static
    {
        $this->type = 'image';
        $this->srcType = is_string($uploadedFile) ? 'string' : 'uploaded-file';
        $this->imageSrc = $uploadedFile;
        return $this;
    }


    public function visibilityPublic(): static
    {
        $this->public = true;
        return $this;
    }

    public function visibilityPrivate(): static
    {
        $this->public = false;
        return $this;
    }

    public function parentModel(Model $model): static
    {
        $this->model = $model;

        if (!$this->directory) {
            $this->setDirectory(
                $this->getDirectoryPath($model)
            );
        }

        return $this;
    }

    public function setDirectory($dir): static
    {
        $this->directory = $dir;
        return $this;
    }

    public function setVariations(array $variations): static
    {
        $this->variations = $variations;
        return $this;
    }

    public function setDisk(Filesystem $disk): static
    {
        $this->disk = $disk;
        return $this;
    }

    public function basename(string $basename): static
    {
        $this->basename = $basename;
        return $this;
    }

    public function withoutOriginal(): static
    {
        $this->withOriginal = false;
        return $this;
    }

    public function keepPreviousImages(): static
    {
        $this->deletePreviousImages = false;
        return $this;
    }

    public function setAddedBy($user): static
    {
        $this->addedBy = is_object($user) ? $user->id : $user;
        return $this;
    }

    public function groupName($group): static
    {
        $this->group = $group;
        return $this;
    }

    public function statusName($status): static
    {
        $this->status = $status;
        return $this;
    }

    protected function getDirectoryPath($model): string
    {
        if (method_exists($model, 'storageDirectory')) {
            return $model->storageDirectory();
        }

        return property_exists($model, 'media_path') ? $model->media_path : '/';
    }

}