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
    final public function __construct(private PhlumDriver $driver, private int $id) {}
    /**
     * @return array<string,ReflectionProperty>
     */
    public static function getProperties(): array
    {
        $refl = new ReflectionClass(static::class);
        // Filter out $id
        $props = array_filter($refl->getProperties(), fn(ReflectionProperty $rp): bool => $rp->getDeclaringClass()->getName() === static::class);
        $propNames = array_map(fn(ReflectionProperty $prop): string => $prop->getName(), $props);
        return array_combine($propNames, $props);
    }
    private static function getTableName(): string
    {
        return hash("sha256", static::class);
    }

    /**
     * @var WeakMap<PhlumDriver, array<string, array<int, WeakReference<self>>>>
     */
    private static WeakMap $objects;
    private static function getObject(PhlumDriver $driver, int $id): ?static
    {
        if(!isset(self::$objects)) {
            self::$objects = new WeakMap();
        }
        return self::$objects->offsetGet($driver)[static::class][$id]?->get() ?? null;
    }
    private static function setObject(PhlumDriver $driver, self $object): void
    {
        if(!isset(self::$objects)) {
            self::$objects = new WeakMap();
        }
        $objects = self::$objects->offsetExists($driver) ? self::$objects->offsetGet($driver) : [];
        if(!array_key_exists(static::class, $objects)) {
            $objects[static::class] = [];
        }
        $objects[static::class][$object->id] = WeakReference::create($object);
        self::$objects->offsetSet($driver, $objects);
    }

    /**
     * @param PhlumDriver $driver
     * @param array<string,mixed> $props
     * @return static
     * @phan-suppress PhanTypeInstantiateAbstractStatic
     */
    public static function create(PhlumDriver $driver, array $props): static
    {
        if(static::isAbstract()) {
            throw new \LogicException("Cannot call PhlumTable::get on abstract class");
        }
        foreach($props as $key => &$value) {
            $value = self::getTransformer(static::getProperties()[$key])->from($driver, $value);
        }
        $self = new static($driver, $driver->create(static::getTableName(), array_values($props)));
        foreach(static::getProperties() as $property) {
            $property->setAccessible(true);
            $property->setValue($self, self::getTransformer($property)->to($driver, $props[$property->getName()]));
        }
        static::setObject($driver, $self);
        return $self;
    }

    /**
     * @param PhlumDriver $driver
     * @param int $id
     * @return static
     * @phan-suppress PhanTypeInstantiateAbstractStatic
     */
    public static function get(PhlumDriver $driver, int $id): static
    {
        if(static::isAbstract()) {
            throw new \LogicException("Cannot call PhlumTable::get on abstract class");
        }
        $self = static::getObject($driver, $id);
        if(is_null($self)) {
            $self = new static($driver, $id);
            $props = $driver->read(static::getTableName(), $id);
            foreach(array_values(static::getProperties()) as $i => $property) {
                $property->setAccessible(true);
                $property->setValue($self, self::getTransformer($property)->to($driver, $props[$i]));
            }
        }
        return $self;
    }
    public function write(): void
    {
        $props = [];
        foreach(static::getProperties() as $property) {
            $props[] = self::getTransformer($property)->from($this->driver, $property->getValue($this));
        }
        $this->driver->update(static::getTableName(), $this->id, $props);
    }

    /**
     * @var null|WeakReference<PhlumObject>
     */
    private ?WeakReference $phlumObject = null;
    public function getPhlumObject(callable $creation): PhlumObject
    {
        $obj = $this->phlumObject?->get();
        if(is_null($obj)) {
            $obj = $creation($this);
            $this->phlumObject = WeakReference::create($obj);
        }
        return $obj;
    }

    /**
     * @var WeakMap<ReflectionProperty,Transformer>|null
     */
    private static ?WeakMap $transformerMap = null;
    private static function getTransformer(ReflectionProperty $property): Transformer
    {
        $transformerMap = self::$transformerMap;
        if(is_null($transformerMap)) {
            $transformerMap = self::$transformerMap = new WeakMap();
        }
        if(!$transformerMap->offsetExists($property)) {
            $transformerMap->offsetSet($property, self::getTransformerForType($property->getType()));
        }
        return $transformerMap->offsetGet($property);
    }

    private static function getTransformerForType(ReflectionType $property): Transformer
    {
        if(!$property instanceof \ReflectionNamedType) {
            throw new \LogicException("Default transformer for union type not supported");
        }
        $type = $property->getName();
        if($type === "int" || $type === "float" || $type === "string") {
            return new PassthroughTransformer();
        }
        if(is_subclass_of($type, PhlumObject::class)) {
            return new PhlumObjectTransformer($type);
        }
        throw new \LogicException("Could not determine default transformer for $type");
    }
}