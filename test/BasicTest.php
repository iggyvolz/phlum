<?php

declare(strict_types=1);

namespace iggyvolz\phlum\test;

use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
    public function testTest(): void
    {
        $this->assertSame(1, 1);
    }
}
