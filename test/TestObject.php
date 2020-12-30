<?php

declare(strict_types=1);

namespace iggyvolz\phlum\test;

use iggyvolz\phlum\PhlumObject;
use iggyvolz\phlum\Attributes\TableReference;

#[TableReference(TestTable::class)]
class TestObject extends PhlumObject
{
    use TestObject_phlum;
}
