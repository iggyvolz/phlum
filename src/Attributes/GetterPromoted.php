<?php


namespace iggyvolz\phlum\Attributes;


interface GetterPromoted
{
    /**
     * @return array{0:class-string,1:list<mixed>}
     */
    public function getGetterAttribute(): array;
}