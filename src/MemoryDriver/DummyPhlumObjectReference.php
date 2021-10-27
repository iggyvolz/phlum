<?php


namespace iggyvolz\phlum\MemoryDriver;


use iggyvolz\phlum\PhlumDriver;
use iggyvolz\phlum\PhlumObjectReference;
use JetBrains\PhpStorm\Immutable;

#[Immutable]
class DummyPhlumObjectReference extends PhlumObjectReference
{
    public function __construct(public PhlumDriver $driver, string $class)
    {
        parent::__construct($this->driver, $class);
    }

    public function getDriver(): PhlumDriver
    {
        return $this->driver;
    }
}
