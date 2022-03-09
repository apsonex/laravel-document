<?php

namespace Apsonex\LaravelDocument\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Document extends Model
{

    const TO_BE_DELETED = "to_be_deleted";

    protected $table = 'documents';

    protected $guarded = ['id'];

    protected $casts = [
        'variations' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (self $doc) {
            $doc->uuid ??= Str::uuid();
            $doc->group ??= 'default';
            $doc->order ??= 1;
            $doc->media_path ??= md5(Str::uuid());
        });
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function toArray(): array
    {
        $data = parent::toArray();

        foreach ($data['variations'] ?? [] as $name => $variation) {
            $data['variations'][$name] = [
                ...$variation,
                'url' => $this->getUrl($variation['path'])
            ];
        }

        return [
            ...$data,
            'url' => $this->getUrl($data['path']),
        ];
    }

    public function getUrl($path): string
    {
        return asset(Storage::disk($this->visibility === 'public' ? 'public' : 'private')->url($path));
    }
}