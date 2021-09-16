<?php

namespace iggyvolz\phlum;

use Iggyvolz\Uuiddb\UuidDb;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class PhlumObject
{
    /**
     * This does NOT create a *new* entity; but initializes it to a given UUID
     * Use ::create() to make a new entity
     * Do NOT do new MyObject(Uuid::v4());
     * @param UuidInterface $id
     */
    private final function __construct(
        private UuidInterface $id
    )
    {
    }

    private ?PhlumObjectSchema $schema = null;

    /**
     * Get the schema for the object
     * Lazy-loaded: don't call until you actually need to read a property
     */
    private function getSchema(): PhlumObjectSchema
    {
        return $this->schema ??= UuidDb::get(Uuid::uuid5($this->id, "data"), PhlumObjectSchema::class);
    }

    public function __serialize(): array
    {
        return ["id" => $this->id];
    }
    public function __unserialize(array $data): void
    {
        $this->id = $data["id"];
    }

    public static function get(UuidInterface $id): static
    {
        return new static($id);
    }
}