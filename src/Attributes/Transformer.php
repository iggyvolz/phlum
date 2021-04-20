<?php

namespace iggyvolz\phlum\Attributes;

interface Transformer
{
    /**
     * Function called when the data is serialized
     * @param mixed $val Value within PHP
     * @return mixed Value to be passed to serialization function
     */
    public function from(mixed $val): mixed;
    /**
     * Function called when the data is unserialized
     * @param mixed $val Value returned from unserialization function
     * @return mixed Value to be returned
     */
    public function to(mixed $val): mixed;
}
