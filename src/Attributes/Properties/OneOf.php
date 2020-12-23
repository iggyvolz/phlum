<?php

declare(strict_types=1);

namespace iggyvolz\phlum\Attributes\Properties;

use Attribute;
use RuntimeException;
use iggyvolz\phlum\Attributes\Properties\SizedInteger;

#[Attribute]
class OneOf implements PhlumProperty
{
    private SizedInteger $index;
    private array $options;
    public function __construct(mixed ...$options)
    {
        $this->options = $options;
        $this->index = new SizedInteger(0, count($options));
    }
    public function getWidth():int
    {
        return $this->index->getWidth();
    }
    public function read(BinaryStream $stream):mixed
    {
        return $this->options[$this->index->read($stream)];
    }
    public function write(BinaryStream $stream, mixed $val):void
    {
        $idx = array_search($val, $this->options);
        if($idx === false) {
            throw new RuntimeException("Option not found");
        }
        $this->index->write($stream, $idx);
    }
}