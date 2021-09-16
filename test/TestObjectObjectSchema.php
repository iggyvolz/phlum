<?php

declare(strict_types=1);

namespace iggyvolz\phlum\test;

use iggyvolz\phlum\MemoryDriver\AllIndex;
use iggyvolz\phlum\MemoryDriver\SearchIndex;
use iggyvolz\phlum\MemoryDriver\UniqueSearchIndex;
use iggyvolz\phlum\PhlumObjectSchema;

#[AllIndex]
class TestObjectObjectSchema extends PhlumObjectSchema
{
    public ?int $a;
    #[SearchIndex]
    public int $b = 1234;
    #[UniqueSearchIndex]
    public int $u;
}
