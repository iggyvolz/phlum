<?php

namespace iggyvolz\phlum\MemoryDriver;

use iggyvolz\phlum\PhlumDriver;
use iggyvolz\phlum\PhlumObjectReference;
use iggyvolz\phlum\PhlumObjectSchema;
use SplObjectStorage;

class MemoryDriver extends PhlumDriver
{
    /**
     * @var array<string,SplObjectStorage<PhlumObjectReference,PhlumObjectSchema>>
     */
    public array $memory = [];
    public function __construct()
    {
    }

    public function create(PhlumObjectSchema $data): PhlumObjectReference
    {
        $tableName = $data::class;
        if(!array_key_exists($tableName, $this->memory)) {
            $this->memory[$tableName] = new SplObjectStorage();
        }
        $this->memory[$tableName]->offsetSet($ref = new DummyPhlumObjectReference($this, $data::class), $data);
        return $ref;
    }

    public function read(PhlumObjectReference $reference): ?PhlumObjectSchema
    {
        if(!$reference instanceof DummyPhlumObjectReference) {
            throw new \LogicException();
        }
        $tableName = $reference->class;
        if(array_key_exists($tableName, $this->memory) && $this->memory[$tableName]->offsetExists($reference)) {
            return $this->memory[$tableName]->offsetGet($reference);
        }
        return null;
    }

    public function update(PhlumObjectSchema $data): void
    {
        // No-op; the object will already update
    }

    public function delete(PhlumObjectSchema $data): void
    {
        $tableName = $data::class;
        if(array_key_exists($tableName, $this->memory)) {
            $this->memory[$tableName]->offsetUnset($data);
        }
    }

    /**
     * @return list<PhlumObjectReference>
     */
    public function getAll(string $table): array
    {
        return iterator_to_array($this->memory[$table] ?? new SplObjectStorage(), false);
    }
}
