<?php

declare(strict_types=1);

namespace iggyvolz\phlum\test;

use iggyvolz\phlum\MemoryDriver\MemoryDriver;
use iggyvolz\phlum\PhlumDriver;
use iggyvolz\phlum\PhlumObjectReference;
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
        $b = 1234; // default value
        $x = TestObject::create(driver: $this->driver, a: $a = null, u: 1);
        $xId = $x->getReference();
        $y = TestObject::get($xId);
        $this->assertSame($x, $y);
        $this->assertSame($a, $x->getA());
        $this->assertSame($b, $x->getb());
        $wr = \WeakReference::create($x);
        unset($x);
        unset($y);
        // We should have lost all references to $x and $y
        $this->assertNull($wr->get());
        $z = TestObject::get($xId);
        $this->assertSame($a, $z->getA());
        $this->assertSame($b, $z->getb());
    }
    public function testUpdate(): void
    {
        $x = TestObject::create(driver: $this->driver, a: 1234, b: 5678, u: 1);
        $xId = $x->getReference();
        $x->setA($a = 6789);
        $this->assertSame($a, $x->getA());
        $wr = \WeakReference::create($x);
        unset($x);
        // We should have lost all references to $x
         $this->assertNull($wr->get());
        $y = TestObject::get($xId);
        $this->assertSame($a, $y->getA());
    }
    public function testRef(): void
    {
        $x = TestObject::create(driver: $this->driver, a: $a = 1234, b: $b = 5678, u: 1);
        $ref = TestObjectWithRef::create(driver: $this->driver, ref: $x->getReference());
        $refid = $ref->getReference();
        $this->assertSame($x, $ref->getRef()->get());
        $wrx = \WeakReference::create($x);
        $wrRef = \WeakReference::create($ref);
        unset($x);
        unset($ref);
        $this->assertNull($wrx->get());
        $this->assertNull($wrRef->get());
        $ref = TestObjectWithRef::get($refid);
        $this->assertSame($a, $ref->getRef()->get()->getA());
        $this->assertSame($b, $ref->getRef()->get()->getB());
    }

    public function testGetAll(): void
    {
        $x = TestObject::create(driver: $this->driver, a: 1234, b: 5678, u: 1);
        $y = TestObject::create(driver: $this->driver, a: 1234, b: 6789, u: 2);
        $z = TestObject::create(driver: $this->driver, a: 2345, b: 6789, u: 3);
        $result = array_map(fn(PhlumObjectReference $ref): TestObject => $ref->get(), TestObject::getAll(driver: $this->driver));
        $this->assertContains($x, $result);
        $this->assertContains($y, $result);
        $this->assertContains($z, $result);
        $this->assertSame(3, count($result));
    }

    public function testSearch(): void
    {
        $x = TestObject::create(driver: $this->driver, a: 1234, b: 5678, u: 1);
        $y = TestObject::create(driver: $this->driver, a: 1234, b: 6789, u: 2);
        $z = TestObject::create(driver: $this->driver, a: 2345, b: 6789, u: 3);
        $result = array_map(fn(PhlumObjectReference $ref): TestObject => $ref->get(), TestObject::searchB(driver: $this->driver, input: 6789));
        $this->assertNotContains($x, $result);
        $this->assertContains($y, $result);
        $this->assertContains($z, $result);
        $this->assertSame(2, count($result));
    }

    public function testUniqueSearch(): void
    {
        $x = TestObject::create(driver: $this->driver, a: 1234, b: 5678, u: 1);
        $y = TestObject::create(driver: $this->driver, a: 1234, b: 6789, u: 2);
        $z = TestObject::create(driver: $this->driver, a: 2345, b: 6789, u: 3);
        $result = TestObject::searchU(driver: $this->driver, input: 3)?->get();
        $this->assertSame($z, $result);
        $result = TestObject::searchU(driver: $this->driver, input: 4);
        $this->assertNull($result);
    }
}
