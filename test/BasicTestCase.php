<?php

declare(strict_types=1);

namespace iggyvolz\phlum\test;

use iggyvolz\phlum\PhlumObject;
use iggyvolz\phlum\PhlumSchema;
use iggyvolz\phlum\Attributes\Access;
use iggyvolz\phlum\Attributes\Properties\Int64;

class BasicTestCase extends PhlumObject
{
    use BasicTestCase_phlum;

    public static function getSchema(): PhlumSchema
    {
        return new class extends PhlumSchema {
            #[Int64]
            public int $foo;
            #[Int64]
            #[Access(Access::PROTECTED, Access::PRIVATE)]
            public int $protectedReadOnly;
        };
    }
}
