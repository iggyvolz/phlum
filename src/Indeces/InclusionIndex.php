<?php

namespace iggyvolz\phlum\Indeces;

use iggyvolz\phlum\PhlumDriver;
use ReflectionClass;
use ReflectionProperty;

/**
 * Index which returns a list of things
 * Examples: getting all entries in a table, all entries with a particular fixed condition
 */
interface InclusionIndex extends Index
{
    /**
     * @param ReflectionClass|ReflectionProperty $target Item that the index is placed on
     * @param PhlumDriver $driver
     * @return list<int>
     */
    public function get(ReflectionClass|ReflectionProperty $target, PhlumDriver $driver): array;
}
