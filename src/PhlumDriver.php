<?php

namespace iggyvolz\phlum;

abstract class PhlumDriver
{
    abstract public function create(PhlumTable $data): PhlumObjectReference;
    abstract public function read(PhlumObjectReference $reference): ?PhlumTable;
    abstract public function update(PhlumTable $data): void;
    abstract public function delete(PhlumTable $data): void;
}
