<?php

declare(strict_types=1);

namespace iggyvolz\phlum\Attributes\Properties;

use Attribute;

#[Attribute]
class SizedInteger extends Integer
{
    /**
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @param bool $minInclusive Whether the minimum is inclusive
     * @param bool $maxInclusive Whether the minimum is inclusive
     */
    public function __construct(private int $min, int $max, bool $minInclusive = true, bool $maxInclusive = false)
    {
        if(!$minInclusive) {
            $min++;
        }
        if($maxInclusive) {
            $max--;
        }
        $size = ceil(($max-$min)/8);
        parent::__construct($size);
    }
    
    public function read(BinaryStream $stream):int
    {
        return $this->min + parent::read($stream);
    }
    public function write(BinaryStream $stream, mixed $val):void
    {
        if(!is_int($val)) {
            throw new RuntimeException("Illegal type for Integer: " . get_debug_type($val));
        }
        parent::write($stream, $val - $this->min);
    }
}