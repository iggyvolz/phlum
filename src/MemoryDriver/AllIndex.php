<?php

namespace iggyvolz\phlum\MemoryDriver;

use Attribute;
use iggyvolz\phlum\Indeces\InclusionIndex;
use iggyvolz\phlum\PhlumDriver;
use ReflectionClass;
use ReflectionProperty;
use TypeError;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS)]
class AllIndex implements InclusionIndex
{
    /**
     * @param ReflectionClass|ReflectionProperty $target
     * @param PhlumDriver $driver
     * @return list<int>
     */
    public function get(ReflectionClass|ReflectionProperty $target, PhlumDriver $driver): array
    {
        if (!$driver instanceof MemoryDriver) {
            throw new TypeError(
                static::class . " requires the use of the " . MemoryDriver::class . " driver, "
                . get_debug_type($driver) . " was used"
            );
        }
        if ($target instanceof ReflectionProperty) {
            throw new TypeError(static::class . " must be placed on a class, not a property");
        }
        /**
         * @var string
         */
        $tableName = $target->getMethod("getTableName")->invoke(null);
        return $driver->getAll($tableName);
    }

    public function getMethodName(ReflectionProperty|ReflectionClass $target): string
    {
        return "getAll";
    }
}
