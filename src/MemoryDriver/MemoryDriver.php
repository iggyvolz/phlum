<?php

namespace iggyvolz\phlum\MemoryDriver;

use iggyvolz\phlum\PhlumDriver;

class MemoryDriver extends PhlumDriver
{
    /**
     * @var array<string,array<int,array<string,mixed>|null>>
     */
    private array $memory = [];

    /**
     * @param string $table
     * @param array<string,mixed> $data
     * @return int
     */
    public function create(string $table, array $data): int
    {
        if (!array_key_exists($table, $this->memory)) {
            $this->memory[$table] = [];
        }
        $this->memory[$table][] = $data;
        return array_key_last($this->memory[$table]) ?? throw new \LogicException("Could not insert element into array");
    }

    /**
     * @param string $table
     * @param int $id
     * @return null|array<string,mixed>
     */
    public function read(string $table, int $id): ?array
    {
        return $this->memory[$table][$id] ?? null;
    }

    /**
     * @param string $table
     * @return list<int>
     */
    public function getAll(string $table): array
    {
        return array_keys($this->memory[$table]);
    }


    /**
     * @param string $table
     * @param int $id
     * @param array<string, mixed> $data
     */
    public function update(string $table, int $id, array $data): void
    {
        if (!array_key_exists($id, $this->memory[$table] ?? [])) {
            throw new \RuntimeException("Cannot update record not in table");
        }
        $this->memory[$table][$id] = $data;
    }

    /**
     * @param string $table
     * @param int $id
     */
    public function delete(string $table, int $id): void
    {
        if (!array_key_exists($id, $this->memory[$table] ?? [])) {
            throw new \RuntimeException("Cannot delete record not in table");
        }
        unset($this->memory[$table][$id]);
    }
}
