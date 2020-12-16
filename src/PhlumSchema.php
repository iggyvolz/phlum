<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use iggyvolz\phlum\Attributes\Description;

#[Description("Represents schema as it should be stored by Phlum")]
abstract class PhlumSchema
{
    final public function __construct()
    {
    }
}
