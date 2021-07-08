<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

abstract class PhlumObject
{
    protected function __construct(private PhlumTable $schema)
    {
    }
    // public static function create(PhlumDriver $db, ...):static
    /**
     * @param PhlumObjectReference<static> $reference
     * @return static
     */
    public static function get(PhlumObjectReference $reference): static
    {
        return $reference->get();
    }
    public function getReference(): PhlumObjectReference
    {
        return $this->schema->getReference();
    }
}
