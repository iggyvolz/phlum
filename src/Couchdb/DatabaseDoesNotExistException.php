<?php

namespace iggyvolz\phlum\Couchdb;

use Exception;

class DatabaseDoesNotExistException extends Exception
{
    public function __construct(string $error, string $reason)
    {
        parent::__construct("$error: $reason");
    }
}