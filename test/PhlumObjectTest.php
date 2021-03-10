<?php

declare(strict_types=1);

namespace iggyvolz\phlum\test;

use iggyvolz\phlum\Conditions\EqualTo;
use iggyvolz\phlum\MemoryDriver;
use iggyvolz\phlum\PhlumDriver;
use PHPUnit\Framework\TestCase;

class PhlumObjectTest extends TestCase
{
    private PhlumDriver $driver;
    public function setUp(): void
    {
        $this->driver = new MemoryDriver();
    }
    public function testCreateAndRead(): void
    {
        $x = TestObject::create(driver: $this->driver, a: $a = 1234, b: $b = 5678);
        $xId = $x->getId();
        $y = TestObject::get($this->driver, $xId);
        $this->assertSame($x, $y);
        $this->assertSame($a, $x->getA());
        $this->assertSame($b, $x->getb());
        $wr = \WeakReference::create($x);
        unset($x);
        unset($y);
        // We should have lost all references to $x and $y
        $this->assertNull($wr->get());
        $z = TestObject::get($this->driver, $xId);
        $this->assertSame($a, $z->getA());
        $this->assertSame($b, $z->getb());
    }
    public function testUpdate(): void
    {
        $x = TestObject::create(driver: $this->driver, a: $a = 1234, b: $b = 5678);
        $xId = $x->getId();
        $x->setA($a = 6789);
        $this->assertSame($a, $x->getA());
        $wr = \WeakReference::create($x);
        unset($x);
        // We should have lost all references to $x
        $this->assertNull($wr->get());
        $y = TestObject::get($this->driver, $xId);
        $this->assertSame($a, $y->getA());
    }
    public function testRef(): void
    {
        $x = TestObject::create(driver: $this->driver, a: $a = 1234, b: $b = 5678);
        $ref = TestObjectWithRef::create(driver: $this->driver, reference: $x);
        $refid = $ref->getId();
        $this->assertSame($x, $ref->getReference());
        $wrx = \WeakReference::create($x);
        $wrRef = \WeakReference::create($ref);
        unset($x);
        unset($ref);
        $this->assertNull($wrx->get());
        $this->assertNull($wrRef->get());
        $ref = TestObjectWithRef::get($this->driver, $refid);
        $this->assertSame($a, $ref->getReference()->getA());
        $this->assertSame($b, $ref->getReference()->getB());
    }
    public function testGetAll(): void
    {
        $x = TestObject::create(driver: $this->driver, a: $a = 1234, b: $b = 5678);
        $y = TestObject::create(driver: $this->driver, a: $a = 1234, b: $b = 6789);
        $z = TestObject::create(driver: $this->driver, a: $a = 2345, b: $b = 6789);
        $result = TestObject::getAll(driver: $this->driver, condition: [
            "a" => new EqualTo(1234),
        ]);
        $this->assertContains($x, $result);
        $this->assertContains($y, $result);
        $this->assertNotContains($z, $result);
        $this->assertSame(2, count($result));
    }
    public function testUpdateAll(): void
    {
        $x = TestObject::create(driver: $this->driver, a: $a = 1234, b: $b = 5678);
        $y = TestObject::create(driver: $this->driver, a: $a = 1234, b: $b = 6789);
        $z = TestObject::create(driver: $this->driver, a: $a = 2345, b: $b = 6789);
        TestObject::updateAll(
            driver: $this->driver,
            condition: [
            "a" => new EqualTo(1234),
            ],
            data: [
            "b" => 7890
            ]
        );
        $this->assertSame(1234, $x->getA());
        $this->assertSame(1234, $y->getA());
        $this->assertSame(2345, $z->getA());
        $this->assertSame(7890, $x->getB());
        $this->assertSame(7890, $y->getB());
        $this->assertSame(6789, $z->getB());
    }
}
