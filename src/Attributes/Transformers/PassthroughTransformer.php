<?php

namespace iggyvolz\phlum\Attributes\Transformers;

use iggyvolz\phlum\Attributes\Transformer;
use iggyvolz\phlum\PhlumDriver;
use ReflectionType;

final class PassthroughTransformer implements Transformer
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

    public static function test(?ReflectionType $property): ?static
    {
        if (!$property instanceof \ReflectionNamedType) {
            return null;
        }
        $type = $property->getName();
        if ($type === "int" || $type === "float" || $type === "string") {
            return new static();
        }
        return null;
    }
}
