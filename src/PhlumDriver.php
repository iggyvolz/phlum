<?php

namespace iggyvolz\phlum;

interface PhlumDriver
{
    /**
     * @param string $table
     * @param array<string, string|int|float|null> $data
     * @return int
     */
    public function create(string $table, array $data): int;

    /**
     * @param string $table
     * @param int $id
     * @return array<string, string|int|float|null>
     */
    public function read(string $table, int $id): array;

    /**
     * @param string $table
     * @param array<string, Condition> $condition
     * @return list<int>
     */
    public function readMany(
        string $table,
        array $condition
    ): array;

    /**
     * @param string $table
     * @param int $id
     * @param array<string, string|int|float|null> $data
     */
    public function update(string $table, int $id, array $data): void;
    /**
     * @param string $table
     * @param array<string, Condition> $condition
     * @param array<string, string|int|float|null> $data
     */
    public function updateMany(string $table, array $condition, array $data): void;

    /**
     * @param string $table
     * @param int $id
     */
    public function delete(string $table, int $id): void;

    /**
     * @param string $table
     * @param array<string, Condition> $condition
     */
    public function deleteMany(string $table, array $condition): void;
}
