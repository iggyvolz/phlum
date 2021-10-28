<?php

namespace iggyvolz\phlum;

interface JsonDeserializable
{
    public static function jsonDeserialize(mixed $data): static;
}