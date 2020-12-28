<?php


namespace iggyvolz\phlum\Attributes;


use iggyvolz\phlum\PhlumDriver;

interface Transformer
{
    function from(PhlumDriver $driver, mixed $val): int|string|float|null;
    function to(PhlumDriver $driver, int|string|float|null $val): mixed;
}