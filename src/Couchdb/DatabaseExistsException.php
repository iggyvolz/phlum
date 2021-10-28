<?php

namespace iggyvolz\phlum\Couchdb;

use Exception;

class DatabaseExistsException extends Exception
{
    public function __construct(string $error, string $reason)
    {
        parent::__construct("$error: $reason");
    }
}