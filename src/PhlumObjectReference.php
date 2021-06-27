<?php

namespace iggyvolz\phlum;

use JetBrains\PhpStorm\Immutable;

#[\Attribute]
#[Immutable]
class PhlumObjectReference
{
    /**
     * PhlumObjectReference constructor.
     * @param string $class
     */
    public function __construct(
        public string $class
    ) {
    }
}
