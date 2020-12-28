<?php


namespace iggyvolz\phlum\test;


use iggyvolz\phlum\Attributes\TableReference;
use iggyvolz\phlum\PhlumObject;

#[TableReference(TestObjectWithRefTable::class)]
class TestObjectWithRef extends PhlumObject
{
    use TestObjectWithRef_phlum;
}