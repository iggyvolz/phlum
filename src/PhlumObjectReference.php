<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use Ramsey\Uuid\UuidInterface;

/**
 * Temporary container for a Phlum object
 * Used to signal to PhlumObjectTransformer that this is a PhlumObject
 */
class PhlumObjectReference
{
    public function __construct(private UuidInterface $uuid)
    {
    }
    public function toObject(): PhlumObject
    {
        return PhlumObject::get($this->uuid) ?? throw new \RuntimeException("Could not resolve UUID");
    }
}
