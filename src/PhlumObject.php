<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

abstract class PhlumObject
{
    // public static function create(PhlumDriver $db, ...):static
    public abstract static function get(PhlumDriver $driver, int $id): static;
    public abstract function getId(): int;
}
