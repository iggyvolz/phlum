<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use Ramsey\Uuid\UuidInterface;
use ReflectionClass;

abstract class PhlumObject
{
    public function __construct()
    {
        $this->register();
    }

    /**
     * @var array<class-string<PhlumTable>,class-string<PhlumObject>> $objectClasses
     */
    private static array $objectClasses = [];
    /**
     * Reference of UUID => object
     * @var array<string,\WeakReference<self>> $references
     */
    private static array $references = [];

    /**
     * @psalm-suppress UnsafeInstantiation
     */
    public static function get(UuidInterface $id): ?static
    {
        // Attempt to get reference from UUID
        if (!is_null($object = self::softGet($id))) {
            return $object;
        }
        $tableObject = PhlumTable::get($id);
        if (is_null($tableObject)) {
            return null;
        }
        $objectClass = self::$objectClasses[get_class($tableObject)] ??= self::getObjectClass(get_class($tableObject));
        // Attempt to get existing reference again - might have been created during this function
        if (!is_null($object = self::softGet($id))) {
            return $object;
        }
        $object = new $objectClass($tableObject);
        if ($object instanceof static) {
            $object->register();
            return $object;
        }
        return null;
    }

    /**
     * Attempt to get an object of a given UUID, giving up if it is not already in memory
     * @internal
     * @psalm-suppress UnsafeInstantiation
     */
    public static function softGet(UuidInterface $id): ?static
    {
        if (array_key_exists($id->getBytes(), self::$references)) {
            $ref = self::$references[$id->getBytes()];
            if (!is_null($obj = $ref->get()) && $obj instanceof static) {
                return $obj;
            }
        }
        return null;
    }

    /**
     * @param class-string<PhlumTable> $tableClass
     * @return class-string<PhlumObject>
     */
    private static function getObjectClass(string $tableClass): string
    {
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, self::class)) {
                $parameter = (new ReflectionClass($class))->getConstructor()?->getParameters()[0] ?? null;
                $childClass = $parameter?->getType();
                if ($childClass instanceof \ReflectionNamedType && $childClass->getName() === $tableClass) {
                    return $class;
                }
            }
        }
        throw new \LogicException("Could not resolve object class for $tableClass");
    }

    abstract public function getId(): UuidInterface;

    /**
     * @return array<mixed,mixed>
     */
    abstract public function __serialize(): array;
    /**
     * @param array<mixed,mixed> $data
     */
    abstract public function __unserialize(array $data): void;

    /**
     * @psalm-suppress InvalidPropertyAssignmentValue ???
     */
    protected function register(): void
    {
        self::$references[$this->getId()->getBytes()] = \WeakReference::create($this);
    }

    /**
     * @param PhlumDatabase $db
     * @return iterable<static>
     */
    abstract public static function getAll(PhlumDatabase $db): iterable;

    public function toReference(): PhlumObjectReference
    {
        return new PhlumObjectReference($this->getId());
    }
    // Created in trait, as this has a variable number and type of parameters
    // public static function create(PhlumDatabase $db, ...):static

    public function __destruct()
    {
        $ref = self::$references[$this->getId()->getBytes()] ?? null;
        if ($ref && $ref->get() === $this) {
            unset(self::$references[$this->getId()->getBytes()]);
        }
    }

    abstract public function getSchema(): PhlumTable;
}
