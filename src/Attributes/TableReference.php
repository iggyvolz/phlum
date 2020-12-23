<?php

declare(strict_types=1);

namespace iggyvolz\phlum\Attributes;

use Attribute;

#[Attribute]
class TableReference
{
    public function __construct(public string $table)
    {
    }
}
