<?php
declare(strict_types=1);

namespace iggyvolz\phlum;


use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UuidReference
{

    public function __construct(private string $uuid)
    {
    }

    public static function fromUuid(UuidInterface $val)
    {
        return new self($val->getBytes());
    }

    public function toObject(): UuidInterface
    {
        return Uuid::fromBytes($this->uuid);
    }
}