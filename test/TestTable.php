<?php

declare(strict_types=1);

namespace iggyvolz\phlum\test;

use iggyvolz\phlum\PhlumTable;
use iggyvolz\phlum\Attributes\Properties\Int64;

class TestTable extends PhlumTable
{
    public ?int $a;
    public int $b;
}
