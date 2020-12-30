<?php

namespace iggyvolz\phlum;

use iggyvolz\classgen\ClassGenerator;
use Stringable;

class HelperGeneratorFactory extends ClassGenerator
{

    protected function isValid(string $class): bool
    {
        return str_ends_with($class, "_phlum");
    }

    protected function generate(string $class): string|Stringable
    {
        $parentClass = substr($class, 0, -strlen("_phlum"));
        if (!class_exists($parentClass) || !is_subclass_of($parentClass, PhlumObject::class)) {
            throw new \LogicException("Invalid parent class $parentClass for $class");
        }
        return new HelperGenerator($parentClass);
    }
}
