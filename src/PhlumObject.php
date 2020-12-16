<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

abstract class PhlumObject
{
    abstract public static function getSchema(): PhlumSchema;
}
