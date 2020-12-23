<?php

declare(strict_types=1);

namespace iggyvolz\phlum\Attributes\Properties;

use RuntimeException;
use wapmorgan\BinaryStream\BinaryStream;

#[Description("A Phlum property")]
interface PhlumProperty
{
    public function getWidth():int;
    public function read(BinaryStream $stream):mixed;
    public function write(mixed $val, BinaryStream $stream):void;
}