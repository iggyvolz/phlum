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
        return new HelperGenerator(substr($class, 0, -strlen("_phlum")));
    }
}