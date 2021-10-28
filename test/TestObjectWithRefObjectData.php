<?php

namespace iggyvolz\phlum\test;

use iggyvolz\phlum\MemoryDriver\AllIndex;
use iggyvolz\phlum\PhlumObjectReference;
use iggyvolz\phlum\PhlumObjectData;

#[AllIndex]
class TestObjectWithRefObjectData extends PhlumObjectData
{
    #[ReferenceTo(TestObject::class)]
    public PhlumObjectReference $ref;
}
