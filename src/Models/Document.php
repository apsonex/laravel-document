<?php

namespace Apsonex\Document\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class Document extends Model
{

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
}