<?php


namespace iggyvolz\phlum\Attributes;


interface SetterParameterPromoted
{
    /**
     * @return array{0:class-string,1:list<mixed>}
     */
    public function getSetterParameterAttribute(): array;
}