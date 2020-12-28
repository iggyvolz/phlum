<?php


namespace iggyvolz\phlum;

class MemoryDriver implements PhlumDriver
{
    /**
     * @var array<string,list<array<string,int|string|float|null>|null>>>
     */
    private array $memory = [];
    public function create(string $table, array $data): int
    {
        if(!array_key_exists($table, $this->memory)) {
            $this->memory[$table] = [];
        }
        $id = count($this->memory[$table]);
        $this->memory[$table][]=$data;
        return $id;
    }

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
        return array_filter($keys, function(int $key) use($table, $condition): bool {
            foreach($condition as $k => $cond) {
                if(!is_null($cond) && !$cond->check($this->memory[$table][$key][$k])) {
                    return false;
                }
            }
            return true;
        });

    }

    public function update(string $table, int $id, array $data): void
    {
        $this->memory[$table][$id] = array_merge($this->memory[$table][$id], $data);
    }

    public function updateMany(string $table, array $condition, array $data): void
    {
        foreach($this->readMany($table, $condition) as $id) {
            $this->update($table, $id, $data);
        }
    }

    public function delete(string $table, int $id): void
    {
        $this->memory[$table][$id] = null;
    }

    public function deleteMany(string $table, array $condition): void
    {
        foreach($this->readMany($table, $condition) as $id) {
            $this->delete($table, $id);
        }
    }
}