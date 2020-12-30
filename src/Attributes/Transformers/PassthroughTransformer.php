<?php

namespace iggyvolz\phlum\Attributes\Transformers;

use iggyvolz\phlum\Attributes\Transformer;
use iggyvolz\phlum\PhlumDriver;

class PassthroughTransformer implements Transformer
{

    public function from(PhlumDriver $driver, mixed $val): int|string|float|null
    {
        if (!is_int($val) && !is_string($val) && !is_float($val) && !is_null($val)) {
            throw new \TypeError("Invalid type " . get_debug_type($val) . " for " . self::class);
        }
        return $val;
    }

    public function to(PhlumDriver $driver, float|int|string|null $val): mixed
    {
        return $val;
    }
}
