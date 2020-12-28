<?php


namespace iggyvolz\phlum;


abstract class Condition
{
    abstract public function check(int|float|string|null $val): bool;
}