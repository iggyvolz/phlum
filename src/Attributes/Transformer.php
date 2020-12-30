<?php

namespace iggyvolz\phlum\Attributes;

use iggyvolz\phlum\PhlumDriver;

interface Transformer
{
    public function from(PhlumDriver $driver, mixed $val): int|string|float|null;
    public function to(PhlumDriver $driver, int|string|float|null $val): mixed;
}
