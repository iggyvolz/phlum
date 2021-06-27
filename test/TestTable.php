<?php

declare(strict_types=1);

namespace iggyvolz\phlum\test;

use iggyvolz\phlum\MemoryDriver\AllIndex;
use iggyvolz\phlum\PhlumTable;

#[AllIndex]
class TestTable extends PhlumTable
{
    public ?int $a;
    #[SearchIndex]
    public int $b;
    #[UniqueSearchIndex]
    public int $u;
}
