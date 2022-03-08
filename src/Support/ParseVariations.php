<?php

namespace Apsonex\Document\Support;

use Illuminate\Support\Collection;

class ParseVariations
{

    public static function parse($variations): Collection
    {
        $parsed = [];

        $enum = enum_exists(ImageSizes::class) ? (new \ReflectionEnum(ImageSizes::class)) : null;

        foreach ($variations as $variation) {
            if (str($variation)->startsWith('dimension:')) {
                $afterColon = str($variation)->after(':');
                $dimension = $afterColon->contains(',') ? $afterColon->before(',') : $afterColon;
                $dims = str($dimension)->explode('x');
                $name = $afterColon->contains(',') ? $afterColon->afterLast(',')->slug()->toString() : "{$dims[0]}x{$dims[1]}";

                $parsed[$name] = [
                    'name'   => $name,
                    'width'  => (int)$dims[0],
                    'height' => (int)$dims[1]
                ];
                continue;
            }

            $upper = strtoupper($variation);

            if ($enum && ($dimension = $enum->getConstant($upper))) {
                $dims = str($dimension)->explode('x');
                $parsed[$prefix = strtolower($variation)] = [
                    'name'   => $prefix,
                    'width'  => (int)$dims[0],
                    'height' => (int)$dims[1]
                ];
            }
        }

        return collect($parsed);
    }

}