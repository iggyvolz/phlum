<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use ReflectionClass;
use iggyvolz\phlum\PhlumSchema;
use wapmorgan\BinaryStream\BinaryStream;

class PhlumDatabase
{
    public function __construct(private string $dataDir) {
    }
    public function getDataDir(): string
    {
        return $this->dataDir;
    }
    public function reset(): void
    {
        foreach(scandir($this->dataDir) as $file) {
            // Clear old DB
            if($file[0] !== ".") unlink($this->dataDir . "/" . $file); 
        }
    }
}