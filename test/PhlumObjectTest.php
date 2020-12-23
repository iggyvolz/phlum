<?php

declare(strict_types=1);

namespace iggyvolz\phlum\test;

use PHPUnit\Framework\TestCase;
use iggyvolz\phlum\PhlumDatabase;

class PhlumObjectTest extends TestCase
{
    private string $dir = __DIR__ . "/../db";
    private PhlumDatabase $db;
    public function setUp(): void
    {
        $this->db = new PhlumDatabase($this->dir);
        $this->db->reset();
    }
    public function testReadWrite(): void
    {
        $x = TestObject::create(db: $this->db, a: 1234, b: 5678);
        $y = TestObject::get($this->db, 0);
        $this->assertSame($x->getA(), $y->getA());
        $this->assertSame($x->getB(), $y->getB());
    }
}