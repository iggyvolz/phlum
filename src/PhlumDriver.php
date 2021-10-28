<?php

namespace iggyvolz\phlum;

use iggyvolz\phlum\Couchdb\Couchdb;
use iggyvolz\phlum\Couchdb\DatabaseExistsException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Psr\Http\Client\ClientExceptionInterface;

class PhlumDriver
{
    public function __construct(private Couchdb $database)
    {
    }

    /**
     * @param class-string<PhlumObjectData> $dataClass
     * @return string
     */
    private static function getDatabaseName(string $dataClass): string
    {
        $name = strtolower($dataClass);
        // Replace any \ with / to preserve these characters
        $name = str_replace("\\", "/", $name);
        // Replace any other invalid characters with _
        $name = str_replace("/[^a-z0-9_$()+/-]/", "_", $name);
        return "phlum_$name";
    }

    public function create(PhlumObjectData $data): string
    {
        $databaseName = self::getDatabaseName(get_class($data));
        try {
            $this->database->createDatabase($databaseName);
        } catch (DatabaseExistsException) {
            // This is okay
        }
        return $this->database->createDocument(
            $databaseName,
            $data->id,
            $data->jsonSerialize()
        );
    }
    public function readAny(string $id): ?PhlumObjectData
    {
    }
    /**
     * @template T of PhlumObjectData
     * @param class-string<T> $class
     * @return ?T
     */
    public function read(string $id, string $class): ?PhlumObjectData
    {
    }
    public function update(PhlumObjectData $data): void
    {
    }
    public function delete(PhlumObjectData $data): void
    {
    }
    public function getUuid(): string
    {
    }
}
