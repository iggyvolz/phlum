<?php


namespace iggyvolz\phlum\Conditions;


use iggyvolz\phlum\Condition;

class EqualTo extends Condition
{
    public function __construct(private float|int|string|null $valToCheck) {}
    public function check(float|int|string|null $val): bool
    {
        return $this->valToCheck === $val;
    }
}