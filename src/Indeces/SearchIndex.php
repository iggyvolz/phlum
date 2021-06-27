<?php

namespace iggyvolz\phlum\Indeces;

use iggyvolz\phlum\PhlumDriver;
use ReflectionClass;
use ReflectionProperty;

/**
 * Index which takes an input and returns a list of things
 * Examples: getting all entries with a particular value, getting all entries containing a particular word
 */
interface SearchIndex extends Index
{
    /**
     * @param ReflectionClass|ReflectionProperty $target Item that the index is placed on
     * @param PhlumDriver $driver
     * @param mixed $input
     * @return list<int>
     */
    public function get(ReflectionClass|ReflectionProperty $target, PhlumDriver $driver, mixed $input): array;

    public function getType(ReflectionClass|ReflectionProperty $target): string;
}
