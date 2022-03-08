<?php

namespace Apsonex\Document\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use PhpParser\PrettyPrinter\Standard;

class ImageFactory
{

    protected \Intervention\Image\Image $image;

    protected bool $private = false;

    protected array $variations = [];

    protected Collection $variationDimensions;

    public static function make(UploadedFile|string $file): static
    {
        $self = (new static());

        $self->variationDimensions = collect([]);

        $self->image = (new ImageManager(['driver' => 'imagick']))->make($file);

        return $self;
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

    public function saveWithVariations($path, $variations = []): array
    {
        $data = $this->save($path, $this->image);

        $variations = ParseVariations::parse($variations);

        if ($variations->isNotEmpty()) {
            $this->image->backup();
            $batch = now()->getTimestamp();

            foreach ($variations as $name => $variation) {
                $variation = [
                    ...$variation,
                    'batch'     => $batch,
                    'name'      => $name,
                    'directory' => $data['directory'],
                ];
                $data['variations'][$name] = $this->saveVariation($variation, $this->image);

                $this->image->reset();
            }
        }

        return $data;
    }

    public function saveVariation($variation, \Intervention\Image\Image $image = null): array
    {
        $image = $image ?? $this->image;

        $fileName = str($image->basename)->beforeLast('.')->toString();

        $directory = $variation['directory'] . '/variations';

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

        $visibility = $this->private ? 'private' : 'public';

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
            'disk'       => $this->private ? config('document.disk.private') : config('document.disk.public'),
        ];

        $image = null;

        return $data;

        //        if ($this->variationDimensions->isNotEmpty()) {
        //
        //            $this->image->backup();
        //
        //            $batch = now()->getTimestamp();
        //
        //            File::ensureDirectoryExists($dir = $dir . '/variations');
        //
        //            foreach ($this->variationDimensions as $variationName => $variation) {
        //                $this->image
        //                    ->fit($variation['width'], $variation['height'])
        //                    ->encode(
        //                        $this->image->extension
        //                    );
        //
        //                $path = vsprintf("%s/%s", [
        //                    $dir = ($variation['suffix'] . '/variations'),
        //                    $filename = ($variation['name'] . '-' . $batch . '.' . $this->image->extension)
        //                ]);
        //
        //                $disk->put($path, $this->image->getEncoded(), $visibility);
        //
        //                $this->variations[$variationName] = [
        //                    'batch'      => $batch,
        //                    'mime'       => $this->image->mime(),
        //                    'extension'  => $this->image->extension,
        //                    'size'       => $disk->size($path),
        //                    'width'      => $variation['width'],
        //                    'height'     => $variation['height'],
        //                    'directory'  => $dir,
        //                    'filename'   => $filename,
        //                    'path'       => $path,
        //                    'visibility' => $visibility,
        //                    'disk'       => $this->private ? config('document.disk.private') : config('document.disk.public'),
        //                ];
        //
        //                $this->image->reset();
        //            }
        //
        //            $data['variations'] = $this->variations();
        //
        //        }
        //
        //        return $data;
    }

    public function variations(): array
    {
        return $this->variations;
    }

    public static function delete(string $path, $private = false): bool
    {
        return static::disk($private)->delete($path);
    }

    protected static function disk($private): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk($private ? config('document.disk.private') : config('document.disk.public'));
    }

}