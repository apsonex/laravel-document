<?php

namespace Apsonex\LaravelDocument\Support;

use Apsonex\LaravelDocument\Support\ImageDrivers\ImageDriver;
use Apsonex\LaravelDocument\Support\ImageDrivers\RetsBaseObjectImageDriver;
use Apsonex\LaravelDocument\Support\ImageDrivers\UploadedFileImageDriver;
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
        'batchId'    => null,
        'variations' => null,
        'visibility' => 'private',
    ];

    protected bool $persistOriginal = true;

    protected array $processedVariations = [];

    protected int $totalSize = 0;

    public static function make(UploadedFile|string $file): static
    {
        $self = new static();

        $self->driver = new UploadedFileImageDriver($file);

        return $self;
    }

    public static function forRetsBaseObject($object): static
    {
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

    public function batchId(string|int $batchId): static
    {
        $this->data['batchId'] = $batchId;
        return $this;
    }

    public function visibility(): string
    {
        return $this->data['visibility'] === 'public' ? 'public' : 'private';
    }

    public function diskName(): string
    {
        return $this->storageDisk->getConfig()['driver'];
    }

    public function disk(Filesystem $disk): static
    {
        $this->storageDisk = $disk;
        return $this;
    }

    public function directory($dir): static
    {
        $this->data['directory'] = $dir;
        return $this;
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
            'basename'  => str($this->data['basename'] ?: $this->driver->filename())->slug()->toString(),
            'directory' => $this->data['directory'] ?: md5(Str::uuid()),
        ];

        $this->saveOriginal();

        $variations = $this->data['variations'] ? $this->persistVariations() : $this->processedVariations;

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

    protected function saveOriginal()
    {
        if ($this->persistOriginal) {
            $this->processedVariations['original'] = $this->saveVariationToDisk('original', [], true);
        }
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

        $path = implode('/', array_filter([
            $this->data['directory'],
            $this->data['batchId'],
            $basename . '.' . $extension,
        ]));

        $encoded = $original ?
            $this->getImageManager()->encode($this->driver->extension()) :
            $this->getImageManager()->fit($width, $height)->encode($this->driver->extension());

        $this->putDiskToStorage($path, $encoded, $mime);

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

    protected function putDiskToStorage($path, $encoded, $mime)
    {
        $this->storageDisk->put(
            $path,
            $encoded->stream(),
            [
                'mimetype' => $mime
            ]
        );
    }

    protected function makeVariationName($data, $variationName = ''): string
    {
        $variationName = $variationName === 'original' ? '' : $variationName;

        return str($data['basename'] . ' ' . $variationName)->slug()->toString();
    }
}
