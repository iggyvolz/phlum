<?php

namespace iggyvolz\phlum\Indeces;

use ReflectionClass;
use ReflectionProperty;

/**
 * Indeces are declared on the PhlumObjectSchema
 */
interface Index
{
    /**
     * Name of the method that should be generated
     */
    public function getMethodName(ReflectionProperty|ReflectionClass $target): string;
}
