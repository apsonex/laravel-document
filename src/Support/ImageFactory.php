<?php

namespace Apsonex\LaravelDocument\Support;

use Apsonex\LaravelDocument\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ImageFactory
{

    protected \Intervention\Image\Image $image;

    protected bool $private = false;

    protected array $variations = [];

    protected Collection $variationDimensions;
    protected bool $queue = false;

    public static function make(UploadedFile|string $file): static
    {
        $self = (new static());

        return $self->init($file);
    }

    public function init($file): static
    {
        $this->variationDimensions = collect([]);

        $this->image = (new ImageManager(['driver' => 'imagick']))->make($file);

        return $this;
    }

    public static function deleteByPath(string $path, string $diskName = 'public'): bool
    {
        return Storage::disk($diskName)->delete($path);
    }

    public function public(): static
    {
        $this->private = false;
        return $this;
    }

    public function private(): static
    {
        $this->private = true;
        return $this;
    }

    public function getImageInstance(): \Intervention\Image\Image
    {
        return $this->image;
    }

    public function backup($name = null): static
    {
        $this->image->backup($name);
        return $this;
    }

    public function reset($name = null): static
    {
        $this->image->reset($name);
        return $this;
    }

    public function encode($type = 'png'): static
    {
        $this->image->encode($type);
        return $this;
    }

    public function withVariations(array $variations, $pathPrefix): static
    {
        $this->variationDimensions = ParseVariations::parse($variations, $pathPrefix);

        return $this;
    }

    public function getImage(): \Intervention\Image\Image
    {
        return $this->image;
    }

    public function saveWithVariations($path, $variations = []): array
    {
        $data = $this->save($path, $this->image);

        return [
            ...$data,
            'variations' => $this->onlyVariations($data['directory'], $variations),
        ];
    }

    public function parseVariations($variations): Collection
    {
        return ParseVariations::parse($variations);
    }

    public function onlyVariations(string $directory, $variations): array
    {
        $processedVariations = [];

        $variations = $this->parseVariations($variations);

        if ($variations->isNotEmpty()) {
            $this->image->backup();
            $batch = now()->getTimestamp();

            foreach ($variations as $name => $variation) {
                $variation = [
                    ...$variation,
                    'batch'     => $batch,
                    'name'      => $name,
                    'directory' => $directory,
                ];

                $processedVariations[$name] = $this->saveVariation($variation, $this->image);

                $this->image->reset();
            }
        }

        return $processedVariations;
    }

    public function saveVariation($variation, \Intervention\Image\Image $image = null): array
    {
        $image = $image ?? $this->image;

        $fileName = str($image->basename)->beforeLast('.')->toString();

        $directory = $variation['directory'] . '/' . Document::VARIATION_DIR;

        $path = vsprintf('%s/%s.%s', [
            $directory,
            str($fileName . ' ' . $variation['name'] . ' ' . ($variation['batch'] ?? now()->getTimestamp()))->slug()->toString(),
            $image->extension
        ]);

        $data = [
            ...$this->save($path, $image->fit($variation['width'], $variation['height'])->encode($image->extension)),
            'directory' => $directory,
            'batch'     => $variation['batch'] ?? null,
        ];

        $image = null;

        return array_filter($data);
    }

    public function save($path, \Intervention\Image\Image $image = null): array
    {
        $image = $image ?: $this->image;

        $visibility = $this->private === true ? 'private' : 'public';

        $disk = static::disk($this->private);

        $disk->put($path, $image->encode($image->extension), $visibility);

        $data = [
            'type'       => str($image->mime())->startsWith('image/') ? 'image' : 'document',
            'mime'       => $image->mime(),
            'extension'  => $image->extension,
            'size'       => $disk->size($path),
            'width'      => $image->width(),
            'height'     => $image->height(),
            'directory'  => str($path)->before('/')->toString(),
            'filename'   => str($path)->afterLast('/')->toString(),
            'path'       => $path,
            'visibility' => $visibility,
            'disk'       => static::diskName($this->private),
        ];

        $image = null;

        return $data;
    }

    protected static function diskName($private): string
    {
        return $private === true ? 'private' : 'public';
    }

    public function variations(): array
    {
        return $this->variations;
    }

    protected static function disk($private): \Illuminate\Contracts\Filesystem\Filesystem
    {
        //
        return Storage::disk(
            static::diskName($private)
        );
    }

}