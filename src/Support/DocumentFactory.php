<?php

namespace Apsonex\Document\Support;

use Apsonex\Document\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class DocumentFactory
{

    public static function saveImageFor($model, UploadedFile|array $file, $public = true, $variations = [])
    {
        $imageFactory = ImageFactory::make($file);

        $public ? $imageFactory->public() : $imageFactory->private();

        $baseName = str($file->getBasename())->beforeLast('.')->toString();

        $path = vsprintf("%s/%s", [$model->media_path ?? md5(Str::uuid()), str($baseName . ' ' . now()->getTimestamp())->slug()->toString() . '.jpg']);

        $data = $imageFactory->saveWithVariations($path, $variations);

        return Document::create([
            ...$data,
            'documentable_type' => get_class($model),
            'documentable_id'   => $model->id,
        ]);
    }

}