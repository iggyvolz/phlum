<?php


namespace iggyvolz\phlum\Attributes;


interface CreatePromoted
{
    /**
     * @return array{0:class-string,1:list<mixed>}
     */
    public function getCreateAttribute(): array;
}