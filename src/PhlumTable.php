<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use iggyvolz\phlum\Attributes\Transformer;
use iggyvolz\phlum\Attributes\Transformers\PassthroughTransformer;
use iggyvolz\phlum\Attributes\Transformers\PhlumObjectTransformer;
use ReflectionClass;
use ReflectionProperty;
use ReflectionType;
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
    final public function __construct(private PhlumDriver $driver, private int $id)
    {
    }
    /**
     * @return array<string, ReflectionProperty>
     */
    public static function getProperties(): array
    {
        $refl = new ReflectionClass(static::class);
        // Filter out $id
        $props = array_values(
            array_filter(
                $refl->getProperties(),
                fn(ReflectionProperty $rp): bool => $rp->getDeclaringClass()->getName() === static::class
            )
        );
        return array_combine(
            array_map(fn(ReflectionProperty $rp): string => $rp->getName(), $props),
            $props
        );
    }
    private static function getTableName(): string
    {
        return hash("sha256", static::class);
    }

    /**
     * @var null|WeakMap<PhlumDriver, array<string, array<int, WeakReference<self>>>>
     */
    private static ?WeakMap $objects = null;
    // phpcs:disable
    private static function getObject(PhlumDriver $driver, int $id): ?static
    {
    // phpcs:enable
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
             * @var WeakMap<PhlumDriver, array<string, array<int, WeakReference<self>>>>
             */
            self::$objects = $sobjects = new WeakMap();
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
     */
    // phpcs:disable
    public static function create(PhlumDriver $driver, array $props): static
    {
    // phpcs:enable
        if (static::isAbstract()) {
            throw new \LogicException("Cannot call PhlumTable::get on abstract class");
        }
        /**
         * @psalm-var mixed $value
         */
        foreach ($props as $key => &$value) {
            $value = self::getTransformer(static::getProperties()[$key])->from($driver, $value);
        }
        /**
         * @var array<string, float|int|null|string> $props
         */
        $self = new static($driver, $driver->create(static::getTableName(), $props));
        foreach (static::getProperties() as $i => $property) {
            $property->setAccessible(true);
            $property->setValue($self, self::getTransformer($property)->to($driver, $props[$i]));
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
    // phpcs:disable
    public static function get(PhlumDriver $driver, int $id): ?static
    {
    // phpcs:enable
        if (static::isAbstract()) {
            throw new \LogicException("Cannot call PhlumTable::get on abstract class");
        }
        $self = static::getObject($driver, $id);
        if (is_null($self)) {
            $self = new static($driver, $id);
            $props = $driver->read(static::getTableName(), $id);
            foreach (static::getProperties() as $i => $property) {
                $property->setAccessible(true);
                $property->setValue($self, self::getTransformer($property)->to($driver, $props[$i]));
            }
        }
        return $self;
    }

    /**
     * @param PhlumDriver $driver
     * @param array<string, Condition> $condition
     * @return array<static>
     */
    // phpcs:disable
    public static function getMany(PhlumDriver $driver, array $condition): array
    {
        // phpcs:enable
        $ret = [];
        foreach ($driver->readMany(static::getTableName(), $condition) as $id) {
            $ret[] = static::get($driver, $id) ??
                throw new \RuntimeException("Database returned invalid object");
        }
        return $ret;
    }

    /**
     * @param PhlumDriver $driver
     * @param array<string, Condition> $condition
     * @param array<string,mixed> $data
     * @return void
     */
    // phpcs:disable
    public static function updateMany(PhlumDriver $driver, array $condition, array $data): void
    {
        // phpcs:enable
        $props = [];
        foreach (static::getProperties() as $i => $property) {
            if (array_key_exists($i, $data)) {
                $props[$i] = self::getTransformer($property)->from($driver, $data[$i]);
            }
        }
        $driver->updateMany(static::getTableName(), $condition, $props);
        // Need to update local copies of objects

        /**
         * /var WeakMap<PhlumDriver, array<string, array<int, WeakReference<self>>>>
         */
        //private static ?WeakMap $objects = null;
        $sobjects = self::$objects;
        if (is_null($sobjects)) {
            return;
        }
        if (!$sobjects->offsetExists($driver)) {
            return;
        }
        /**
         * @var array<string, array<int, WeakReference<self>>> $objects
         */
        $objects = $sobjects->offsetGet($driver);
        foreach ($objects[static::class] as $id => $obj) {
            $self = $obj->get();
            if (is_null($self)) {
                continue; // This object does not exist, skip it
            }
            $props = $driver->read(static::getTableName(), $id);
            foreach (static::getProperties() as $i => $property) {
                if (array_key_exists($i, $props)) {
                    $property->setAccessible(true);
                    $property->setValue($self, self::getTransformer($property)->to($driver, $props[$i]));
                }
            }
        }
    }
    public function write(): void
    {
        $props = [];
        foreach (static::getProperties() as $property) {
            $props[$property->getName()] = self::getTransformer($property)->from(
                $this->driver,
                $property->getValue($this)
            );
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

    /**
     * @var WeakMap<ReflectionProperty,Transformer>|null
     */
    private static ?\WeakMap $transformerMap = null;
    private static function getTransformer(ReflectionProperty $property): Transformer
    {
        $transformerMap = self::$transformerMap;
        if (is_null($transformerMap)) {
            /**
             * @var WeakMap<ReflectionProperty,Transformer>
             */
            $transformerMap = new WeakMap();
            self::$transformerMap = $transformerMap;
        }
        if ($transformerMap->offsetExists($property)) {
            /**
             * @psalm-var mixed
             */
            $transformer = $transformerMap->offsetGet($property);
            if ($transformer instanceof Transformer) {
                return $transformer;
            }
        }
        $transformer = self::getTransformerForType($property->getType());
        $transformerMap->offsetSet($property, $transformer);
        return $transformer;
    }

    private static function getTransformerForType(?ReflectionType $property): Transformer
    {
        if (is_null($property)) {
            throw new \LogicException("Default transformer for untyped property not supported");
        }
        if (!$property instanceof \ReflectionNamedType) {
            throw new \LogicException("Default transformer for union type not supported");
        }
        $type = $property->getName();
        if ($type === "int" || $type === "float" || $type === "string") {
            return new PassthroughTransformer();
        }
        if (is_subclass_of($type, PhlumObject::class)) {
            return new PhlumObjectTransformer($type);
        }
        throw new \LogicException("Could not determine default transformer for $type");
    }
}
