<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use iggyvolz\phlum\Attributes\Properties\PhlumProperty;

abstract class PhlumTable
{
    private function __construct() {}
    /**
     * @return list<PhlumProperty>
     */
    public abstract static function getProperties(): array;
    public static function getRowWidth(): int
    {
        return array_sum(array_map(fn(PhlumProperty $prop): int => $prop->getWidth(), static::getProperties()));
    }
    public static function read(string $dataDir, int $which):static
    {
        
    }
}