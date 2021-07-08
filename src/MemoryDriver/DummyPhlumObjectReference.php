<?php


namespace iggyvolz\phlum\MemoryDriver;


use iggyvolz\phlum\PhlumDriver;
use iggyvolz\phlum\PhlumObjectReference;
use JetBrains\PhpStorm\Immutable;

#[Immutable]
class DummyPhlumObjectReference extends PhlumObjectReference
{
    public function __construct(public PhlumDriver $driver, public string $class)
    {
        parent::__construct($this->driver);
    }

    public function getDriver(): PhlumDriver
    {
        return $this->driver;
    }
}