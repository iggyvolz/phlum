<?php


namespace iggyvolz\phlum\Attributes;


interface IndexPromoted
{
    /**
     * @return array{0:class-string,1:list<mixed>}
     */
    public function getIndexAttribute(string $class): array;
}