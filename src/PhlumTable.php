<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use ReflectionClass;
use WeakMap;
use WeakReference;

abstract class PhlumTable
{
    private static function isAbstract(): bool
    {
        return (new ReflectionClass(static::class))->isAbstract();
    }

    private ?PhlumObjectReference $reference;
    private PhlumDriver $driver;

    public function getReference(): PhlumObjectReference
    {
        if(is_null($this->reference)) {
            throw new \LogicException("Cannot get reference to non-created object");
        }
        return $this->reference;
    }

    public function getDriver(): PhlumDriver
    {
        return $this->driver;
    }

    final public function __construct(PhlumObjectReference|PhlumDriver $referenceOrDriver)
    {
        if($referenceOrDriver instanceof PhlumObjectReference) {
            $this->reference = $referenceOrDriver;
            $this->driver = $referenceOrDriver->driver;
        } else {
            $this->driver = $referenceOrDriver;
        }
    }

    /**
     * @var null|WeakMap<PhlumObjectReference,PhlumObject>
     */
    private static ?WeakMap $objects = null;
    private static function getObject(PhlumObjectReference $reference): ?static
    {
        $sobjects = self::$objects;
        if (is_null($sobjects)) {
            /**
             * @var WeakMap<PhlumObjectReference,PhlumObject>
             */
            $sobjects = new WeakMap();
            self::$objects = $sobjects;
        }
        if (!$sobjects->offsetExists($reference)) {
            return null;
        }
        $result = $sobjects->offsetGet($reference);
        if (!$result instanceof static) {
            return null;
        }
        /**
         * @psalm-var static $result
         */
        return $result;
    }
    private function setObject(PhlumObjectReference $reference): void
    {
        $sobjects = self::$objects;
        if (is_null($sobjects)) {
            /**
             * @var WeakMap<PhlumObjectReference,PhlumObject> $sobjects
             */
            $sobjects = new WeakMap();
            self::$objects = $sobjects;
        }
        $sobjects->offsetSet($reference, $this);
    }

    /**
     * @param array<string,mixed> $props
     * @return $this
     * @throws \ReflectionException
     */
    public function create(array $props): static
    {
        if (static::isAbstract()) {
            throw new \LogicException("Cannot call PhlumTable::get on abstract class");
        }
        foreach($props as $k => $v) {
            $property = (new ReflectionClass(static::class))->getProperty($k);
            $property->setAccessible(true);
            $property->setValue($this, $v);
        }
        $reference = $this->driver->create($this);
        $this->reference = $reference;
        $this->setObject($reference);
        return $this;
    }

    public static function get(PhlumObjectReference $reference): ?static
    {
        if (static::isAbstract()) {
            throw new \LogicException("Cannot call PhlumTable::get on abstract class");
        }
        $self = static::getObject($reference);
        if (is_null($self)) {
            return $reference->driver->read($reference);
        }
        return $self;
    }
    public function write(): void
    {
        $this->getDriver()->update($this);
    }

    /**
     * @var null|WeakReference<PhlumObject>
     */
    private ?WeakReference $phlumObject = null;

    public function getPhlumObject(): PhlumObject
    {
        $obj = $this->phlumObject?->get();
        if (is_null($obj)) {
            try {
                $class = new ReflectionClass(substr(static::class, 0, -strlen("Table")));
            } catch(\ReflectionException) {
                throw new \LogicException("Could not find class " . substr(static::class, 0, -strlen("Table")) . " from " . static::class);
            }
            $this->phlumObject = WeakReference::create($obj = $class->newInstanceWithoutConstructor());
            $constructor = $class->getConstructor();
            $constructor?->setAccessible(true);
            $constructor?->invoke($obj, $this);
        }
        return $obj;
    }
}
