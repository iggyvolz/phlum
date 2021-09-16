<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use Iggyvolz\Generics\Generic;
use Iggyvolz\Generics\T1;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use WeakMap;

/**
 * WARNING: Once you go to a production/persistent database, you MUST NOT change the PhlumObjectSchema definition or any class it touches!
 */
abstract class PhlumObjectSchema
{
    public /* readonly */ UuidInterface $id;
    public function __construct(
        public /* readonly */ PhlumDatabase $database
    ) {
        $this->id = Uuid::uuid4();
    }

    public function __serialize(): array
    {
        return iterator_to_array((function(){
            foreach((new \ReflectionClass(static::class))->getProperties() as $property) {
                if($property->getDeclaringClass() === static::class) {
                    yield $property->getName() => $property->getValue();
                }
            }
        })());
    }

    public function __unserialize(array $data): void
    {
        // TODO: Implement __unserialize() method.
    }


    public function save(): void
    {
        $this->database->save($this->id, igbinary_serialize($this) ?? throw new \RuntimeException("Failed to serialize object"));
    }

    public function delete(): void
    {
        $this->database->delete($this->id);
    }
    public function toPhlumObject(): PhlumObject
    {
        return T1::get($this);
    }
}
