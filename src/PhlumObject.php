<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

abstract class PhlumObject
{
    abstract protected static function getSchema(): PhlumSchema;
}
