<?php


namespace iggyvolz\phlum\test;

use JetBrains\PhpStorm\Immutable;

#[\Attribute]
#[Immutable]
class ReferenceTo
{
    public function __construct(public string $class)
    {
    }
}