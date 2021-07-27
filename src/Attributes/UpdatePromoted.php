<?php


namespace iggyvolz\phlum\Attributes;


interface UpdatePromoted
{
    /**
     * @return array{0:class-string,1:list<mixed>}
     */
    public function getUpdateAttribute(): array;
}