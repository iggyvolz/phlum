<?php

namespace iggyvolz\phlum\Attributes\Transformers;

use iggyvolz\phlum\Attributes\Transformer;
use iggyvolz\phlum\PhlumObject;
use iggyvolz\phlum\PhlumObjectReference;

/**
 * Transforms a PhlumObject into a PhlumObjectReference, to avoid recursive references in serialization
 */
class PhlumObjectTransformer implements Transformer
{
    public function from(mixed $val): mixed
    {
        return $val instanceof PhlumObject ? $val->toReference() : $val;
    }

    public function to(mixed $val): mixed
    {
        return $val instanceof PhlumObjectReference ? $val->toObject() : $val;
    }
}
