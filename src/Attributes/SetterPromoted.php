<?php


namespace iggyvolz\phlum\Attributes;


interface SetterPromoted
{
    /**
     * @return array{0:class-string,1:list<mixed>}
     */
    public function getSetterAttribute(): array;
}