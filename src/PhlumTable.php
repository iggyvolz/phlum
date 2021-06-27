<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use Iggyvolz\SimpleAttributeReflection\AttributeReflection;
use ReflectionClass;
use ReflectionProperty;
use WeakMap;
use WeakReference;

abstract class PhlumTable
{
    private static function isAbstract(): bool
    {
        return (new ReflectionClass(static::class))->isAbstract();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDriver(): PhlumDriver
    {
        return $this->driver;
    }

    final public function __construct(private PhlumDriver $driver, private int $id)
    {
    }
    /**
     * @return array<string, ReflectionProperty>
     */
    public static function getProperties(bool $includeId = false): array
    {
        $refl = new ReflectionClass(static::class);
        $props = $refl->getProperties();
        if(!$includeId) {
            // Filter out $id
            $props = array_values(
                array_filter(
                    $props,
                    fn(ReflectionProperty $rp): bool => $rp->getDeclaringClass()->getName() === static::class
                )
            );
        }
        return array_combine(
            array_map(fn(ReflectionProperty $rp): string => $rp->getName(), $props),
            $props
        );
    }
    public static function getTableName(): string
    {
        return
            AttributeReflection::getAttribute(new ReflectionClass(static::class), TableName::class)?->TableName ?? hash("sha256", static::class);
    }

    /**
     * @var null|WeakMap<PhlumDriver, array<string, array<int, WeakReference<self>>>>
     */
    private static ?WeakMap $objects = null;
    private static function getObject(PhlumDriver $driver, int $id): ?static
    {
        $sobjects = self::$objects;
        if (is_null($sobjects)) {
            /**
             * @var WeakMap<PhlumDriver, array<string, array<int, WeakReference<self>>>>
             */
            $sobjects = new WeakMap();
            self::$objects = $sobjects;
        }
        if (!$sobjects->offsetExists($driver)) {
            return null;
        }
        /**
         * @var array<string, array<int, WeakReference<self>>> $objects
         */
        $objects = $sobjects->offsetGet($driver);
        $result = ($objects[static::class][$id] ?? null)?->get() ?? null;
        if (!$result instanceof static) {
            return null;
        }
        /**
         * @psalm-var static $result
         */
        return $result;
    }
    private static function setObject(PhlumDriver $driver, self $object): void
    {
        $sobjects = self::$objects;
        if (is_null($sobjects)) {
            /**
             * @var WeakMap<PhlumDriver, array<string, array<int, WeakReference<self>>>> $sobjects
             */
            $sobjects = new WeakMap();
            self::$objects = $sobjects;
        }
        /**
         * @var array<string, array<int, WeakReference<self>>> $objects
         */
        $objects = $sobjects->offsetExists($driver) ? $sobjects->offsetGet($driver) : [];
        if (!array_key_exists(static::class, $objects)) {
            $objects[static::class] = [];
        }
        $objects[static::class][$object->id] = WeakReference::create($object);
        $sobjects->offsetSet($driver, $objects);
    }

    /**
     * @param PhlumDriver $driver
     * @param array<string, mixed> $props
     * @return static
     * @phan-suppress PhanTypeInstantiateAbstractStatic
     *   => Checked internally
     */
    public static function create(PhlumDriver $driver, array $props): static
    {
        if (static::isAbstract()) {
            throw new \LogicException("Cannot call PhlumTable::get on abstract class");
        }
        $self = new static($driver, $driver->create(static::getTableName(), $props));
        foreach (static::getProperties() as $i => $property) {
            $property->setAccessible(true);
            $property->setValue($self, $props[$i]);
        }
        static::setObject($driver, $self);
        return $self;
    }

    /**
     * @param PhlumDriver $driver
     * @param int $id
     * @return static|null
     * @phan-suppress PhanTypeInstantiateAbstractStatic
     */
    public static function get(PhlumDriver $driver, int $id): ?static
    {
        if (static::isAbstract()) {
            throw new \LogicException("Cannot call PhlumTable::get on abstract class");
        }
        $self = static::getObject($driver, $id);
        if (is_null($self)) {
            $self = new static($driver, $id);
            $props = $driver->read(static::getTableName(), $id);
            if(is_null($props)) return null;
            foreach (static::getProperties() as $i => $property) {
                $property->setAccessible(true);
                $property->setValue($self, $props[$i]);
            }
        }
        return $self;
    }
    public function write(): void
    {
        $props = [];
        foreach (static::getProperties() as $property) {
            /**
             * @var mixed
             */
            $props[$property->getName()] = $property->getValue($this);
        }
        $this->driver->update(static::getTableName(), $this->id, $props);
    }

    /**
     * @var null|WeakReference<PhlumObject>
     */
    private ?WeakReference $phlumObject = null;

    /**
     * @param callable(PhlumTable):PhlumObject $creation
     * @return PhlumObject
     */
    public function getPhlumObject(callable $creation): PhlumObject
    {
        $obj = $this->phlumObject?->get();
        if (is_null($obj)) {
            $obj = $creation($this);
            $this->phlumObject = WeakReference::create($obj);
        }
        return $obj;
    }
}
