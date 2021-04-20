<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use iggyvolz\phlum\Attributes\Transformers\PhlumObjectTransformer;
use iggyvolz\phlum\Attributes\Transformers\UuidTransformer;
use Ramsey\Uuid\Rfc4122\Fields;
use Ramsey\Uuid\UuidInterface;
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
    public function getId(): UuidInterface
    {
        return $this->id;
    }
    protected function __construct(private UuidInterface $id)
    {
        throw new \LogicException("Object should never be constructed directly");
    }
    /**
     * @return array<string, ReflectionProperty>
     */
    public static function getProperties(bool $includeId = true): array
    {
        $refl = new ReflectionClass(static::class);
        $props = $refl->getProperties();
        if ($includeId) {
            $props["\0"] = new ReflectionProperty(self::class, "id");
        }
        return array_combine(
            array_map(fn(ReflectionProperty $rp): string => $rp->getName(), $props),
            $props
        );
    }

    /**
     * @param PhlumDatabase $db
     * @param array<string,mixed> $props
     * @return static
     */
    public static function create(PhlumDatabase $db, array $props): static
    {
        if (static::isAbstract()) {
            throw new \LogicException("Cannot call PhlumTable::get on abstract class");
        }
        /**
         * @var static $self
         */
        $self = (new ReflectionClass(static::class))->newInstanceWithoutConstructor();
        /**
         * @var mixed $value
         */
        foreach ($props as $key => $value) {
            $self->$key = $value;
        }
        $self->id = $db->getUuid(static::class);
        $db->create($self);
        return $self;
    }

    public static function get(UuidInterface $id): ?self
    {
        $fields = $id->getFields();
        if (!$fields instanceof Fields) {
            throw new \LogicException();
        }
        $db = PhlumDatabase::getDatabase($fields->getNode()->toString());
        return $db?->get($id);
    }

    /**
     * @param PhlumDatabase $db
     * @return \Generator<static>
     */
    public static function getAll(PhlumDatabase $db): \Generator
    {
        foreach ($db->getAll($db->getNodeProvider(static::class)) as $element) {
            if ($element instanceof static) {
                // See if we have an existing object of that ID; if so use that
                $schema = PhlumObject::softGet($element->getId())?->getSchema();
                if ($schema instanceof static) {
                    // Use the already-existing object
                    yield $schema;
                } else {
                    yield $element;
                }
            }
        }
    }

    public function write(): void
    {
        $fields = $this->id->getFields();
        if (!$fields instanceof Fields) {
            throw new \LogicException();
        }
        PhlumDatabase::getDatabase($fields->getNode()->toString())?->update($this);
    }

    /**
     * Map of table => object
     * @var \WeakMap<self,WeakReference<PhlumObject>>|null
     */
    private static null|\WeakMap $phlumObjects = null;

    /**
     * @template T of PhlumObject
     * @param callable(PhlumTable):T $creation
     * @return T
     * @phan-suppress PhanTypeMismatchDeclaredReturn, PhanNonClassMethodCall
     */
    public function getPhlumObject(callable $creation): PhlumObject
    {
        /**
         * @var WeakReference<T>|null $existingObject
         * @phan-var WeakReference<PhlumObject>|null $existingObject
         */
        $existingObject = (self::$phlumObjects?->offsetExists($this) ? self::$phlumObjects?->offsetGet($this) : null);
        $existingObject = $existingObject?->get();
        if ($existingObject) {
            return $existingObject;
        }
        return $this->realGetPhlumObject($creation);
    }


    /**
     * Actually construct the Phlum object
     * @template T of PhlumObject
     * @param callable(PhlumTable):T $creation
     * @return T
     * @phan-suppress PhanTypeMismatchDeclaredReturn, PhanPossiblyNonClassMethodCall
     */
    private function realGetPhlumObject(callable $creation): PhlumObject
    {
        $object = $creation($this);
        if (is_null(self::$phlumObjects)) {
            /**
             * @var WeakMap<PhlumTable,WeakReference<PhlumObject>> $newWeakMap
             */
            $newWeakMap = new WeakMap();
            self::$phlumObjects = $newWeakMap;
        }
        self::$phlumObjects->offsetSet($this, WeakReference::create($object));
        return $object;
    }

    /**
     * @return array<mixed,mixed>
     */
    public function __serialize(): array
    {
        return iterator_to_array((/** @return \Generator<mixed,mixed> */function (): \Generator {
            foreach (self::getProperties() as $name => $reflectionProperty) {
                $reflectionProperty->setAccessible(true);
                $transformers = [
                    new PhlumObjectTransformer(),
                    new UuidTransformer(),
                ];
                /**
                 * @var mixed $val
                 */
                $val = $reflectionProperty->getValue($this);
                foreach ($transformers as $transformer) {
                    /**
                     * @var mixed $val
                     */
                    $val = $transformer->from($val);
                }
                yield $name => $val;
            }
        })());
    }

    /**
     * @param array<mixed,mixed> $data
     */
    public function __unserialize(array $data): void
    {
        foreach (self::getProperties() as $name => $reflectionProperty) {
            $reflectionProperty->setAccessible(true);
            if (array_key_exists($name, $data)) {
                /**
                 * @var mixed $val
                 */
                $val = $data[$name];
                $transformers = [
                    new PhlumObjectTransformer(),
                    new UuidTransformer(),
                ];
                foreach ($transformers as $transformer) {
                    /**
                     * @var mixed $val
                     */
                    $val = $transformer->to($val);
                }
                $reflectionProperty->setValue($this, $val);
            }
        }
    }
}
