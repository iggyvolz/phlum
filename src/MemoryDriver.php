<?php

namespace iggyvolz\phlum;

class MemoryDriver implements PhlumDriver
{
    /**
     * @var array<string,list<list<int|string|float|null>|null>>
     */
    private array $memory = [];

    /**
     * @param string $table
     * @param list<string|int|float|null> $data
     * @return int
     */
    public function create(string $table, array $data): int
    {
        if (!array_key_exists($table, $this->memory)) {
            $this->memory[$table] = [];
        }
        $id = count($this->memory[$table]);
        $this->memory[$table][] = $data;
        return $id;
    }

    /**
     * @param string $table
     * @param int $id
     * @return list<string|int|float|null>
     */
    public function read(string $table, int $id): array
    {
        return $this->memory[$table][$id] ?? throw new \RuntimeException("Index out of range $id for $table");
    }

    /**
     * @param string $table
     * @param list<?Condition> $condition
     * @return list<int>
     */
    public function readMany(string $table, array $condition): array
    {
        $keys = array_keys($this->memory[$table]);
        return array_values(array_filter($keys, function (int $key) use ($table, $condition): bool {
            foreach ($condition as $k => $cond) {
                if (!is_null($cond) && !$cond->check($this->memory[$table][$key][$k] ?? null)) {
                    return false;
                }
            }
            return true;
        }));
    }

    /**
     * @param string $table
     * @param int $id
     * @param array<int, string|int|float|null> $data
     */
    public function update(string $table, int $id, array $data): void
    {
        if (!array_key_exists($id, $this->memory[$table] ?? [])) {
            throw new \RuntimeException("Cannot update record not in table");
        }
        $newRow = array_values(array_replace($this->memory[$table][$id] ?? [], $data));
        $this->memory[$table] = array_values(array_replace($this->memory[$table], [$id => $newRow]));
    }

    /**
     * @param string $table
     * @param list<?Condition> $condition
     * @param array<int, string|int|float|null> $data
     */
    public function updateMany(string $table, array $condition, array $data): void
    {
        foreach ($this->readMany($table, $condition) as $id) {
            $this->update($table, $id, $data);
        }
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
        $this->memory[$table] = array_values(array_replace($this->memory[$table], [$id => null]));
    }

    /**
     * @param string $table
     * @param list<?Condition> $condition
     */
    public function deleteMany(string $table, array $condition): void
    {
        foreach ($this->readMany($table, $condition) as $id) {
            $this->delete($table, $id);
        }
    }
}
