<?php

namespace iggyvolz\phlum\Indeces;

use iggyvolz\phlum\PhlumDriver;
use iggyvolz\phlum\PhlumObjectReference;
use ReflectionClass;
use ReflectionProperty;

/**
 * Index which takes an input and returns one or zero things
 * Like a SearchIndex but only one element should ever be returned
 */
interface UniqueSearchIndex extends Index
{
    /**
     * @param ReflectionClass|ReflectionProperty $target Item that the index is placed on
     * @param PhlumDriver $driver
     * @param mixed $input
     * @return null|PhlumObjectReference
     */
    public function get(ReflectionClass|ReflectionProperty $target, PhlumDriver $driver, mixed $input): ?PhlumObjectReference;

    public function getType(ReflectionClass|ReflectionProperty $target): string;
}
