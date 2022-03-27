<?php

namespace Apsonex\LaravelDocument\Support;

use Apsonex\LaravelDocument\Models\Document;
use Apsonex\LaravelDocument\Support\ImageDrivers\ImageDriver;
use Apsonex\LaravelDocument\Support\ImageDrivers\RetsBaseObjectImageDriver;
use Apsonex\LaravelDocument\Support\ImageDrivers\UploadedFileImageDriver;
use Exception;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImageFactory
{

    protected ImageDriver $driver;

    protected Filesystem $storageDisk;

    protected array $data = [
        'path'       => null,
        'directory'  => null,
        'basename'   => null,
        'variations' => null,
        'visibility' => 'private',
    ];

    protected bool $persistOriginal = true;

    protected array $processedVariations = [];

    protected int $totalSize = 0;

    protected string|int|null $variationBatchId = null;

    public static function make(UploadedFile|string $file): static
    {
        $self = new static();

        $self->driver = new UploadedFileImageDriver($file);

        return $self;
    }

    /**
     * @throws Exception
     */
    public static function forRetsBaseObject($object): static
    {
        if (!class_exists('\Apsonex\Rets\Models\BaseObject')) {
            throw new Exception("\Apsonex\Rets\Models\BaseObject class not exists triggered on \Apsonex\LaravelDocument\Support\ImageFactory@forRetsBaseObject", 500);
        }

        $self = new static();

        $self->driver = new RetsBaseObjectImageDriver($object);

        return $self;
    }

    public static function deleteVariations(Filesystem $disk, array $variations, $deleteEmptyDir = false)
    {
        $self = new static();

        $directories = [];

        foreach ($variations as $variation) {
            $path = $variation['path'];
            $disk->delete($variation['path']);

            if ($deleteEmptyDir && Str::contains($path, ['/'])) {
                $dir = str($path)->beforeLast('/')->toString();

                if ($dir !== "" && !in_array($dir, $directories)) {
                    $directories[] = $dir;
                }
            }
        }

        if ($deleteEmptyDir && !empty($directories)) {
            foreach ($directories as $directory) {
                $self->deleteDirectoryIfEmpty($disk, $directory);
            }
        }
    }

    public function deleteDirectoryIfEmpty(Filesystem $disk, $dir)
    {
        if ($dir === "") return;

        $count = count($disk->allFiles($dir));

        if ($count <= 0) {
            $disk->deleteDirectory($dir);
        }
    }

    public function withoutOriginal(): static
    {
        $this->persistOriginal = false;
        return $this;
    }

    public function basename($basename): static
    {
        $this->data['basename'] = $basename;
        return $this;
    }

    public function variations(array $variations = []): static
    {
        $this->data['variations'] = ParseVariations::parse($variations);
        return $this;
    }

    public function disk(Filesystem $disk): static
    {
        $this->storageDisk = $disk;
        return $this;
    }

    public function batchId($batchId): static
    {
        $this->variationBatchId = $batchId;
        return $this;
    }

    public function directory($dir): static
    {
        $this->data['directory'] = $dir;
        return $this;
    }

    //    public function path($path): static
    //    {
    //        $this->data['path'] = $path;
    //
    //        $this->setUpDirectoryAndBatch();
    //
    //        return $this;
    //    }

    protected function setUpDirectoryAndBatch()
    {
        $path = str($this->data['path']);


    }

    public function visibilityPublic(): static
    {
        $this->data['visibility'] = 'public';
        return $this;
    }

    public function getImageManager(): \Intervention\Image\Image
    {
        return $this->driver->getImageManager();
    }

    public function persist(): array
    {
        $this->processedVariations = [];

        $this->data = [
            ...$this->data,
            'batch'     => $this->variationBatchId ?: now()->getTimestamp(),
            'basename'  => str($this->data['basename'] ?: $this->driver->filename())->slug()->toString(),
            'directory' => $this->data['directory'] ?: md5(Str::uuid()),
        ];

        if ($this->persistOriginal) {
            $this->processedVariations['original'] = $this->saveVariationToDisk('original', [], true);
        }

        $variations = empty($this->data['variations'] ?? []) ? $this->processedVariations : $this->persistVariations();

        return [
            'media_path' => $this->data['directory'],
            'mime'       => $this->driver->mime(),
            'path'       => $this->data['directory'],
            'visibility' => $this->visibility(),
            'disk'       => $this->diskName(),
            'size'       => $this->totalSize,
            'variations' => $variations,
        ];
    }

    protected function persistVariations(): array
    {
        $this->driver->backup();

        foreach ($this->data['variations'] as $variationName => $variationConfig) {
            $this->processedVariations[$variationName] = $this->saveVariationToDisk($variationName, $variationConfig);
            $this->driver->reset();
        }

        return $this->processedVariations;
    }

    protected function saveVariationToDisk(string $variationName, array $variationConfig, $original = false): array
    {
        $width = $variationConfig['width'] ?? $this->getImageManager()->width();
        $height = $variationConfig['height'] ?? $this->getImageManager()->height();
        $basename = $this->makeVariationName($this->data, $variationName);
        $extension = $this->driver->extension();
        $mime = $this->driver->mime();

        $path = vsprintf('%s/%s.%s', [
            $this->data['directory'],
            $basename,
            $extension,
        ]);

        $encoded = $original ? $this->getImageManager() : $this->getImageManager()->fit($width, $height)->encode($this->driver->extension());

        $this->storageDisk->put(
            $path,
            $encoded,
            ['visibility' => $this->data['visibility']]
        );

        $size = $this->storageDisk->size($path);

        $this->totalSize = $this->totalSize + $size;

        $processedVariation = [
            'type'      => str($mime)->startsWith('image/') ? 'image' : 'document',
            'mime'      => $this->driver->mime(),
            'extension' => $extension,
            'size'      => $size,
            'width'     => $width,
            'height'    => $height,
            'filename'  => "{$basename}.{$extension}",
            'path'      => $path,
        ];

        $encoded = null;

        return $processedVariation;
    }

    protected function makeVariationName($data, $variationName = ''): string
    {
        $variationName = $variationName === 'original' ? '' : $variationName;

        $name = $data['basename'] . ' ' . $variationName . ' ' . ($data['batch'] ?? now()->getTimestamp());

        return str($name)->slug()->toString();
    }

    public function visibility(): string
    {
        return $this->data['visibility'] === 'public' ? 'public' : 'private';
    }

    public function diskName(): string
    {
        return $this->storageDisk->getConfig()['driver'];
    }

    protected function mediaPath()
    {
        return $this->data['directory'];
    }
}