<?php

namespace iggyvolz\phlum\Couchdb;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;

/**
 * @todo Make this a full couchDB API and probably yoink it to its own repo
 */
class Couchdb
{
    public function __construct(private ClientInterface $http, private string $baseUrl, private string $authentication)
    {
    }

    /**
     * @throws ClientExceptionInterface
     * @throws UnauthorizedException
     * @throws BadRequestException
     * @throws DatabaseExistsException
     * @throws JsonException
     * @throws UnexpectedOutputException
     */
    public function createDatabase(string $databaseName): void
    {
        $response = $this->http->sendRequest(
            new Request(
                "PUT",
                $this->baseUrl . "/" . $databaseName,
                ["Authentication: " . $this->authentication]
            )
        );
        $body = Json::decode($response->getBody()->getContents());
        switch ($response->getStatusCode()) {
            case 201:
            case 202:
                return;
            case 400:
                throw new BadRequestException($body["error"], $body["reason"]);
            case 401:
                throw new UnauthorizedException($body["error"], $body["reason"]);
            case 412:
                throw new DatabaseExistsException($body["error"], $body["reason"]);
            default:
                throw new UnexpectedOutputException();
        }
    }

    /**
     * @throws DatabaseDoesNotExistException
     * @throws UnauthorizedException
     * @throws ConflictException
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws BadRequestException
     * @throws UnexpectedOutputException
     */
    public function createDocument(string $databaseName, ?string $id, array $data): string
    {
        if (!is_null($id)) {
            $data["_id"] = $id;
        }
        $response = $this->http->sendRequest(
            new Request(
                "POST",
                $this->baseUrl . "/" . $databaseName,
                ["Authentication: " . $this->authentication],
                Json::encode($data)
            )
        );
        $body = Json::decode($response->getBody()->getContents());
        switch ($response->getStatusCode()) {
            case 201:
            case 202:
                return is_string($body["id"] ?? null) ? $body["id"] : throw new UnexpectedOutputException();
            case 400:
                throw new BadRequestException($body["error"], $body["reason"]);
            case 401:
                throw new UnauthorizedException($body["error"], $body["reason"]);
            case 404:
                throw new DatabaseDoesNotExistException($body["error"], $body["reason"]);
            case 409:
                throw new ConflictException($body["error"], $body["reason"]);
            default:
                throw new UnexpectedOutputException();
        }
    }
}
