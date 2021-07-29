<?php


namespace iggyvolz\phlum\Attributes;


interface IndexDriverPromoted
{
    /**
     * @return array{0:class-string,1:list<mixed>}
     */
    public function getIndexDriverAttribute(string $class): array;
}