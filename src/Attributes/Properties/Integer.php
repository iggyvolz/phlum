<?php

declare(strict_types=1);

namespace iggyvolz\phlum\Attributes\Properties;

use Attribute;
use RuntimeException;
use wapmorgan\BinaryStream\BinaryStream;

#[Attribute]
abstract class Integer implements PhlumProperty
{
    public function __construct(private int $width) {}
    public function getWidth():int
    {
        return $this->width;
    }
    public function read(BinaryStream $stream):int
    {
        return $stream->readInteger($this->width * 8);
    }
    public function write(BinaryStream $stream, mixed $val):void
    {
        if(!is_int($val)) {
            throw new RuntimeException("Illegal type for Integer: " . get_debug_type($val));
        }
        $stream->writeInteger($val, $this->width * 8);
    }
}