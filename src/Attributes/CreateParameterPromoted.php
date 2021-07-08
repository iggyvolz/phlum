<?php


namespace iggyvolz\phlum\Attributes;


interface CreateParameterPromoted
{
    /**
     * @return array{0:class-string,1:list<mixed>}
     */
    public function getCreateParameterAttribute(): array;
}