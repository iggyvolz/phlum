<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use Iggyvolz\SimpleAttributeReflection\AttributeReflection;
use ReflectionClass;
use WeakMap;
use WeakReference;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class PhlumObjectData implements \JsonSerializable, JsonDeserializable
{
    private static function isAbstract(): bool
    {
        return (new ReflectionClass(static::class))->isAbstract();
    }

    public function __construct(
        public PhlumDriver $driver,
        public ?string $id = null,
    ) {
    }

    /**
     * @var array<string,PhlumObjectData>
     */
    private static array $objects = [];
    private static function getObject(string $id): ?static
    {
        $result = self::$objects[$id] ?? null;
        if ($result instanceof static) {
            return $result;
        }
        return null;
    }
    private function setObject(string $id): void
    {
        self::$objects[$id] = $this;
    }

    /**
     * @param array<string,mixed> $props
     * @return static
     * @throws \ReflectionException if an invalid property is set
     */
    public function create(array $props): static
    {
        if (static::isAbstract()) {
            throw new \LogicException("Cannot call PhlumObjectData::get on abstract class");
        }
        foreach ($props as $k => $v) {
            $property = (new ReflectionClass(static::class))->getProperty($k);
            $property->setValue($this, $v);
        }
        $this->id ??= $this->driver->create($this);
        $this->setObject($this->id);
        return $this;
    }

    public static function get(PhlumDriver $driver, string $id): ?static
    {
        if (static::isAbstract()) {
            throw new \LogicException("Cannot call PhlumObjectData::get on abstract class");
        }
        return $driver->read($id, static::class);
    }
    public function write(): void
    {
        $this->driver->update($this);
    }

    /**
     * @var null|WeakReference<PhlumObject>
     */
    private ?WeakReference $phlumObject = null;

    public function getPhlumObject(): PhlumObject
    {
        $obj = $this->phlumObject?->get();
        if (is_null($obj)) {
            /**
             * @var ObjectClass $objectClass
             */
            $objectClass = AttributeReflection::getAttribute(new ReflectionClass(static::class), ObjectClass::class);
            $this->phlumObject = WeakReference::create($obj = $objectClass->instantiate($this));
        }
        return $obj;
    }


    public static function jsonDeserialize(mixed $data): static
    {
        // TODO: Implement jsonDeserialize() method.
    }

    public function jsonSerialize(): array
    {
        // TODO: Implement jsonSerialize() method.
    }
}
