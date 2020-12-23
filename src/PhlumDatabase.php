<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use ReflectionClass;
use iggyvolz\phlum\PhlumSchema;
use wapmorgan\BinaryStream\BinaryStream;

class PhlumDatabase
{
    /**
     * @var PhlumSchema[]
     */
    private array $schemas;

    /**
     * @var BinaryStream[]
     */
    private array $streams;
    /**
     * @psalm-param ReflectionClass<PhlumObject>[] $classes
     */
    public function __construct(private string $dir, private array $classes) {
        $this->schemas = array_map(fn(ReflectionClass $rc):PhlumSchema => $rc->getMethod("getSchema")->invoke(null));
        $this->streams = array_map(fn(PhlumSchema $sch):BinaryStream => $sch->open($dir));
    }
}