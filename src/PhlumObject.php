<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

abstract class PhlumObject
{
    // public static function create(PhlumDriver $db, ...):static
    // phpcs:disable
    abstract public static function get(PhlumDriver $driver, int $id): static;
    // phpcs:enable
    abstract public function getId(): int;
}
