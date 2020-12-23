<?php

declare(strict_types=1);

namespace iggyvolz\phlum\Attributes\Properties;

use Attribute;

#[Attribute]
class Byte extends Integer
{
    public function __construct()
    {
        parent::__construct(1);
    }
}