<?php


namespace iggyvolz\phlum\MemoryDriver;

use Attribute;
use iggyvolz\phlum\MemoryDriver\MemoryDriver;
use iggyvolz\phlum\PhlumDriver;
use ReflectionClass;
use ReflectionProperty;
use TypeError;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_PROPERTY)]
class SearchIndex implements \iggyvolz\phlum\Indeces\SearchIndex
{

    public function get(ReflectionClass|ReflectionProperty $target, PhlumDriver $driver, mixed $input): array
    {
        if(!$driver instanceof MemoryDriver) {
            throw new TypeError(static::class . " requires the use of the " . MemoryDriver::class . " driver, " . get_debug_type($driver) . " was used");
        }
        if($target instanceof ReflectionClass) {
            throw new TypeError(static::class . " must be placed on a property, not a class");
        }
        $ids = [];
        /**
         * @var string
         */
        $tableName = $target->getDeclaringClass()->getMethod("getTableName")->invoke(null);
        foreach($driver->getAll($tableName) as $id) {
            if(($driver->read($tableName, $id) ?? [])[$target->getName()] === $input) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    function getMethodName(ReflectionProperty|ReflectionClass $target): string
    {
        return "search" . ucfirst($target->getName());
    }

    public function getType(ReflectionProperty|ReflectionClass $target): string
    {
        if($target instanceof ReflectionClass) {
            throw new TypeError(static::class . " must be placed on a property, not a class");
        }
        $type = $target->getType();
        if(is_null($type)) {
            return "mixed";
        } else {
            return $this->getReflectionType($type);
        }
    }

    private function getReflectionType(\ReflectionType $type): string
    {
        if($type instanceof \ReflectionNamedType) {
            return $type->getName();
        } elseif($type instanceof \ReflectionUnionType) {
            return implode("|", array_map(fn(\ReflectionType $rt): string => $this->getReflectionType($rt), $type->getTypes()));
        } else {
            throw new TypeError("Unknown ReflectionType " . get_debug_type($type));
        }
    }
}