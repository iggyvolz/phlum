<?php

declare(strict_types=1);

namespace iggyvolz\phlum\test;

use iggyvolz\phlum\PhlumDatabase;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PhlumObjectTest extends TestCase
{
    private PhlumDatabase $db;

    private static function rrmdir(string $dir): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        rmdir($dir);
    }

    public function setUp(): void
    {
        $testdir = __DIR__ . "/db";
        if (file_exists($testdir)) {
            self::rrmdir($testdir);
        }
        mkdir($testdir);
        $this->db = PhlumDatabase::initialize($testdir, [
            TestTable::class,
            TestObjectWithRefTable::class,
        ]);
    }

    public function testCreateAndRead(): void
    {
        $x = TestObject::create(db: $this->db, a: $a = null, b: $b = 5678);
        $xId = $x->getId();
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
        $this->assertInstanceOf(TestObject::class, $z);
        /** @var TestObject $z */
        $this->assertSame($a, $z->getA());
        $this->assertSame($b, $z->getB());
    }

    public function testUpdate(): void
    {
        $x = TestObject::create(db: $this->db, a: 1234, b: 5678);
        $xId = $x->getId();
        $x->setA($a = 6789);
        $this->assertSame($a, $x->getA());
        $wr = \WeakReference::create($x);
        unset($x);
        // We should have lost all references to $x
        $this->assertNull($wr->get());
        $y = TestObject::get($xId);
        $this->assertInstanceOf(TestObject::class, $y);
        /** @var TestObject $y */
        $this->assertSame($a, $y->getA());
    }

    public function testRef(): void
    {
        $x = TestObject::create(db: $this->db, a: $a = 1234, b: $b = 5678);
        $ref = TestObjectWithRef::create(db: $this->db, reference: $x);
        $refid = $ref->getId();
        $this->assertSame($x, $ref->getReference());
        $wrx = \WeakReference::create($x);
        $wrRef = \WeakReference::create($ref);
        unset($x);
        unset($ref);
        $this->assertNull($wrx->get());
        $this->assertNull($wrRef->get());
        $ref = TestObjectWithRef::get($refid);
        $this->assertInstanceOf(TestObjectWithRef::class, $ref);
        /** @var TestObjectWithRef $ref */
        $this->assertSame($a, $ref->getReference()->getA());
        $this->assertSame($b, $ref->getReference()->getB());
    }

    public function testGetAll(): void
    {
        $x = TestObject::create(db: $this->db, a: 1234, b: 5678);
        $y = TestObject::create(db: $this->db, a: 1234, b: 6789);
        $z = TestObject::create(db: $this->db, a: 2345, b: 6789);
        $resultIter = TestObject::getAll(db: $this->db);
        $result = iterator_to_array((function () use ($resultIter) {
            foreach ($resultIter as $key => $value) {
                yield $key => $value;
            }
        })());
        $this->assertContains($x, $result);
        $this->assertContains($y, $result);
        $this->assertContains($z, $result);
        $this->assertSame(3, count($result));
    }
}
