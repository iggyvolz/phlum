<?php

namespace iggyvolz\phlum\MemoryDriver;

use iggyvolz\phlum\PhlumDriver;
use iggyvolz\phlum\PhlumObjectReference;
use iggyvolz\phlum\PhlumTable;
use SplObjectStorage;

class MemoryDriver extends PhlumDriver
{
    /**
     * @var array<string,SplObjectStorage<PhlumObjectReference,PhlumTable>>
     */
    public array $memory = [];
    public function __construct()
    {
    }

    public function create(PhlumTable $data): PhlumObjectReference
    {
        $tableName = $data::class;
        if(!array_key_exists($tableName, $this->memory)) {
            $this->memory[$tableName] = new SplObjectStorage();
        }
        $this->memory[$tableName]->offsetSet($ref = new DummyPhlumObjectReference($this, $data::class), $data);
        return $ref;
    }

    public function read(PhlumObjectReference $reference): ?PhlumTable
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

    public function update(PhlumTable $data): void
    {
        // No-op; the object will already update
    }

    public function delete(PhlumTable $data): void
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

    public function getById(string $table, int $id): ?PhlumObjectReference
    {
        foreach(self::getAll($table) as $ref) {
            if(spl_object_id($ref) === $id) return $ref;
        }
        return null;
    }
}
