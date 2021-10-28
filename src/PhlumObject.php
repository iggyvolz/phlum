<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

abstract class PhlumObject
{
    protected function __construct(PhlumObjectData $schema)
    {
    }

//    public static function get(PhlumDriver $driver, string $id): static
//    {
//        return new static($driver->read($id));
//    }
}
