<?php

namespace iggyvolz\phlum;

#[\Attribute(\Attribute::TARGET_CLASS)]
/**
 * @template T of PhlumObject
 */
class ObjectClass
{
    /**
     * @param class-string<T> $class
     */
    public function __construct(private string $class)
    {
    }

    /**
     * @return T
     */
    public function instantiate(PhlumObjectData $data): PhlumObject
    {
        return new ($this->class)($data);
    }
}