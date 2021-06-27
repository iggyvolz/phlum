<?php


namespace iggyvolz\phlum;

use JetBrains\PhpStorm\Immutable;

#[\Attribute]
#[Immutable]
class TableName
{
    public function __construct(
        public string $TableName
    )
    {
    }
}