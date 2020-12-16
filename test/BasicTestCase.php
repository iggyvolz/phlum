<?php

declare(strict_types=1);

namespace iggyvolz\phlum\test;

use iggyvolz\phlum\PhlumObject;
use iggyvolz\phlum\PhlumSchema;

class BasicTestCase extends PhlumObject
{
    use BasicTestCase_phlum;

    public static function getSchema(): PhlumSchema
    {
        return new class extends PhlumSchema {
            public int $foo;
        };
    }
}
