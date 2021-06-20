<?php

namespace iggyvolz\phlum;

use iggyvolz\phlum\Attributes\Transformer;
use ReflectionProperty;
use ReflectionType;
use WeakMap;

abstract class PhlumDriver
{
    public function __construct()
    {
        /**
         * @var WeakMap<ReflectionProperty, Transformer>
         */
        $this->transformerMap = new WeakMap();
    }
    /**
     * @param string $table
     * @param array<string, string|int|float|null> $data
     * @return int
     */
    abstract public function create(string $table, array $data): int;

    /**
     * @param string $table
     * @param int $id
     * @return array<string, string|int|float|null>
     */
    abstract public function read(string $table, int $id): array;

    /**
     * @param string $table
     * @param array<string, Condition> $condition
     * @return list<int>
     */
    abstract public function readMany(
        string $table,
        array $condition
    ): array;

    /**
     * @param string $table
     * @param int $id
     * @param array<string, string|int|float|null> $data
     */
    abstract public function update(string $table, int $id, array $data): void;
    /**
     * @param string $table
     * @param array<string, Condition> $condition
     * @param array<string, string|int|float|null> $data
     */
    abstract public function updateMany(string $table, array $condition, array $data): void;

    /**
     * @param string $table
     * @param int $id
     */
    abstract public function delete(string $table, int $id): void;

    /**
     * @param string $table
     * @param array<string, Condition> $condition
     */
    abstract public function deleteMany(string $table, array $condition): void;

    /**
     * @var WeakMap<ReflectionProperty,Transformer>
     */
    private WeakMap $transformerMap;
    public function getTransformer(ReflectionProperty $property): Transformer
    {
        if ($this->transformerMap->offsetExists($property)) {
            /**
             * @psalm-var mixed
             */
            $transformer = $this->transformerMap->offsetGet($property);
            if ($transformer instanceof Transformer) {
                return $transformer;
            }
        }
        $transformer = $this->getTransformerForType($property->getType());
        $this->transformerMap->offsetSet($property, $transformer);
        return $transformer;
    }

    private function getTransformerForType(?ReflectionType $property): Transformer
    {
        foreach ($this->defaultTransformers as $potentialTransformer) {
            if ($transformer = $potentialTransformer::test($property)) {
                return $transformer;
            }
        }
        throw new \LogicException("Could not determine default transformer");
    }

    /**
     * @var class-string<Transformer>[]
     */
    private array $defaultTransformers = [];

    /**
     * @param class-string<Transformer> $transformerClass
     */
    public function registerDefaultTransformer(string $transformerClass): void
    {
        $this->defaultTransformers[] = $transformerClass;
    }
}
