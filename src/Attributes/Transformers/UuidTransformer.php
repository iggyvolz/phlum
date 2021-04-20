<?php

namespace iggyvolz\phlum\Attributes\Transformers;

use iggyvolz\phlum\Attributes\Transformer;
use iggyvolz\phlum\UuidReference;
use Ramsey\Uuid\UuidInterface;

/**
 * Transforms a PhlumObject into a PhlumObjectReference, to avoid recursive references in serialization
 */
class UuidTransformer implements Transformer
{
    public function from(mixed $val): mixed
    {
        return $val instanceof UuidInterface ? UuidReference::fromUuid($val) : $val;
    }

    public function to(mixed $val): mixed
    {
        return $val instanceof UuidReference ? $val->toObject() : $val;
    }
}
