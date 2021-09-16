<?php

namespace iggyvolz\phlum;

use Iggyvolz\Uuiddb\UuidLibrary;
use Ramsey\Uuid\UuidInterface;

final class PhlumSchemaDatabase extends UuidLibrary
{
    public function __construct(private string $path)
    {
    }

    public function save(UuidInterface $id, string $value): void
    {
        try {
            $dba = dba_open($this->path, "c", "lmdb");
            dba_replace($id->getBytes(), $value, $dba);
        } finally {
            if($dba !== false) {
                dba_close($dba);
            }
        }
    }

    public function delete(UuidInterface $id)
    {
        try {
            $dba = dba_open($this->path, "c", "lmdb");
            dba_delete($id->getBytes(), $dba);
        } finally {
            if($dba !== false) {
                dba_close($dba);
            }
        }
    }

    public function get(UuidInterface $id, ?string $expectedType = null): ?PhlumObjectSchema
    {
        // TODO keep cache of UUID => WeakRef<PhlumSchema>; return if already exists

        if(!is_null($expectedType) && is_subclass_of($expectedType, PhlumObjectSchema::class))
        {
            return null;
        }
        try {
            $dba = dba_open($this->path, "r", "lmdb");
            $value = dba_fetch($id->getBytes(), $dba);
        } finally {
            if($dba !== false) {
                dba_close($dba);
            }
        }
        if($value === false) {
            return null;
        }
        $value = igbinary_unserialize($value);
        if(!$value instanceof PhlumObjectSchema) {
            return null;
        }
        if(is_null($expectedType) || $value instanceof $expectedType) {
            return $value;
        }
        return null;
    }

    public function getPriority(): int
    {
        return 11;
    }
}