<?php

namespace iggyvolz\phlum;

abstract class PhlumDriver
{
    public function __construct()
    {
    }
    /**
     * @param string $table
     * @param array<string, mixed> $data
     * @return int
     */
    abstract public function create(string $table, array $data): int;

    /**
     * @param string $table
     * @param int $id
     * @return null|array<string, mixed>
     */
    abstract public function read(string $table, int $id): ?array;

    /**
     * @param string $table
     * @param int $id
     * @param array<string, mixed> $data
     */
    abstract public function update(string $table, int $id, array $data): void;

    /**
     * @param string $table
     * @param int $id
     */
    abstract public function delete(string $table, int $id): void;
}
