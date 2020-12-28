<?php


namespace iggyvolz\phlum\Attributes\Transformers;


use iggyvolz\phlum\Attributes\Transformer;
use iggyvolz\phlum\PhlumDriver;
use iggyvolz\phlum\PhlumObject;

class PhlumObjectTransformer implements Transformer
{
    /**
     * PhlumObjectTransformer constructor.
     * @param class-string<PhlumObject> $class
     */
    public function __construct(
        private string $class
    ) {}
    function from(PhlumDriver $driver, mixed $val): int|string|float|null
    {
        if(is_null($val)) {
            return null;
        }
        if(!$val instanceof $this->class) {
            throw new \TypeError("Invalid type ".get_debug_type($val)." for ".self::class);
        }
        assert($val instanceof PhlumObject);
        return $val->getId();
    }

    function to(PhlumDriver $driver, float|int|string|null $val): mixed
    {
        if(is_null($val)) {
            return null;
        }
        if(!is_int($val)) {
            throw new \TypeError("Invalid type ".get_debug_type($val)." for ".self::class);
        }
        return $this->class::get($driver, $val);
    }
}