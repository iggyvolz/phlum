<?php


namespace iggyvolz\phlum\Attributes;


interface CreateDriverPromoted
{
    /**
     * @return array{0:class-string,1:list<mixed>}
     */
    public function getCreateDriverAttribute(): array;
}