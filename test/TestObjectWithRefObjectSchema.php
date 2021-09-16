<?php

namespace iggyvolz\phlum\test;

use iggyvolz\phlum\MemoryDriver\AllIndex;
use iggyvolz\phlum\PhlumObjectReference;
use iggyvolz\phlum\PhlumObjectSchema;

#[AllIndex]
class TestObjectWithRefObjectSchema extends PhlumObjectSchema
{
    #[ReferenceTo(TestObject::class)]
    public PhlumObjectReference $ref;
}
