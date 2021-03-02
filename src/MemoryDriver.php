<?php

namespace iggyvolz\phlum;

class MemoryDriver implements PhlumDriver
{
    /**
     * @var array<string,list<array<string,int|string|float|null>|null>>
     */
    private array $memory = [];

    /**
     * @param string $table
     * @param array<string,string|int|float|null> $data
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
     * @return array<string,string|int|float|null>
     */
    public function read(string $table, int $id): array
    {
        return $this->memory[$table][$id] ?? throw new \RuntimeException("Index out of range $id for $table");
    }

    /**
     * @param string $table
     * @param array<string,Condition> $condition
     * @return list<int>
     */
    public function readMany(string $table, array $condition): array
    {
        return array_values(iterator_to_array((function () use ($table, $condition): \Generator {
            foreach ($this->memory[$table] as $rowId => $row) {
                $keep = true;
                foreach ($condition as $key => $conditionObject) {
                    if (!$conditionObject->check($row[$key] ?? null)) {
                        $keep = false;
                        break;
                    }
                }
                if ($keep) {
                    yield $rowId;
                }
            }
        })()));
    }

    /**
     * @param string $table
     * @param int $id
     * @param array<string, string|int|float|null> $data
     */
    public function update(string $table, int $id, array $data): void
    {
        if (!array_key_exists($id, $this->memory[$table] ?? [])) {
            throw new \RuntimeException("Cannot update record not in table");
        }
        foreach ($data as $key => $value) {
            /**
             * @psalm-suppress PropertyTypeCoercion
             *      Psalm believes that $id may not exist in $this->memory[$table]
             *      This, we may be expanding this from a list<float|int|string|null> to an
             *      array<int, float|int|string|null>.  But we checked that above.
             */
            $this->memory[$table][$id][$key] = $value;
        }
    }

    /**
     * @param string $table
     * @param array<string, Condition> $condition
     * @param array<string, string|int|float|null> $data
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
     * @param array<string, Condition> $condition
     */
    public function deleteMany(string $table, array $condition): void
    {
        foreach ($this->readMany($table, $condition) as $id) {
            $this->delete($table, $id);
        }
    }
}
