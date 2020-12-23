<?php

declare(strict_types=1);

namespace iggyvolz\phlum\Attributes\Properties;

use Attribute;
use RuntimeException;

#[Attribute]
#[Description("Controls access for a Phlum property")]
class Int64 implements PhlumProperty
{
    public function getWidth():int
    {
        return 8;
    }
    public function read(BinaryStream $stream):int
    {
        return $stream->readInteger(64);
    }
    public function write(mixed $val, BinaryStream $stream):void
    {
        if(!is_int($val)) {
            throw new RuntimeException("Illegal type for Int64: " . get_debug_type($val));
        }
        $stream->writeInteger($val, 64);
    }
}