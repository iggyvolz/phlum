<?php


namespace iggyvolz\phlum\Indeces;

use ReflectionClass;
use ReflectionProperty;

/**
 * Indeces are declared on the PhlumTable
 */
interface Index
{
    /**
     * Name of the method that should be generated
     */
    function getMethodName(ReflectionProperty|ReflectionClass $target): string;
}