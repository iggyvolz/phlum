<?php

namespace iggyvolz\phlum\test;

use iggyvolz\phlum\MemoryDriver\AllIndex;
use iggyvolz\phlum\PhlumObjectReference;
use iggyvolz\phlum\PhlumTable;

#[AllIndex]
class TestObjectWithRefTable extends PhlumTable
{
    #[ReferenceTo(TestObject::class)]
    public PhlumObjectReference $ref;
}
