<?php

namespace Apsonex\LaravelDocument\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int         id
 * @property int         order
 * @property string      type
 * @property string      mime
 * @property string      path
 * @property string      disk
 * @property int         size
 * @property array       variations
 * @property string      visibility
 * @property int         documentable_id
 * @property string      documentable_type
 * @property null|string status
 * @property string      group
 * @property string      media_path
 *
 * @method static create(array $data)
 */
class Document extends Model
{

    const TO_BE_DELETED = "to_be_deleted";

    const VARIATION_DIR = 'variations';

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

    public function isPublicDisk(): bool
    {
        return $this->disk === 'public';
    }

    public function diskName(): string
    {
        return $this->disk === 'public' ? 'public' : 'private';
    }

    public function fullPath(): string
    {
        return Storage::disk($this->diskName())->path($this->path);
    }

    public function variationsDirectory(): string
    {
        return \str($this->fullPath())->beforeLast('/') . '/' . static::VARIATION_DIR;
    }
}