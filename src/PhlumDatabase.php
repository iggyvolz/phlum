<?php

namespace iggyvolz\phlum;

use Iggyvolz\Uuiddb\UuidDb;
use Iggyvolz\Uuiddb\UuidLibrary;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class PhlumDatabase extends UuidLibrary
{
    public function __construct()
    {
    }

    public function get(UuidInterface $id, ?string $expectedType = null): ?PhlumObject
    {
        if(!is_null($expectedType) && is_subclass_of($expectedType, PhlumObject::class))
        {
            return null;
        }
        $dataUuid = Uuid::uuid5($id, "data");
        if(($data = UuidDb::get($dataUuid, PhlumObjectSchema::class)) && $data instanceof PhlumObjectSchema) {
            $object = $data->toPhlumObject();
            if(is_null($expectedType) || $object instanceof $expectedType) {
                return $object;
            }
        }
        return null;
    }
    public function getPriority(): int
    {
        return 10;
    }
}