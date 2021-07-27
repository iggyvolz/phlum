<?php


namespace iggyvolz\phlum\Attributes;


interface UpdateParameterPromoted
{
    /**
     * @return array{0:class-string,1:list<mixed>}
     */
    public function getUpdateParameterAttribute(): array;
}