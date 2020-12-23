<?php

declare(strict_types=1);

namespace iggyvolz\phlum\Attributes\Properties;

use Attribute;

#[Attribute]
class Int32 extends Integer
{
    public function __construct()
    {
        parent::__construct(4);
    }
}