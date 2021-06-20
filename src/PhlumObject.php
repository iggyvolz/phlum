<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

abstract class PhlumObject
{
    // public static function create(PhlumDriver $db, ...):static
    abstract public static function get(PhlumDriver $driver, int $id): static;
    abstract public function getId(): int;
    // public static function getMany(PhlumDriver $driver, array<static-properties, Condition> $condition): list<static>
}
