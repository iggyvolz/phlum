<?php

namespace iggyvolz\phlum;

use JetBrains\PhpStorm\Immutable;

/**
 * @template T of PhlumObject
 */
#[Immutable]
abstract class PhlumObjectReference
{
    /**
     * PhlumObjectReference constructor.
     * @param PhlumDriver $driver
     * @param class-string<T> $class
     */
    protected function __construct(
        public PhlumDriver $driver,
        public string $class = PhlumObject::class
    )
    {
    }

    /**
     * @return T
     */
    public function get(): PhlumObject
    {
        return $this->driver->read($this)->getPhlumObject();
    }
}
