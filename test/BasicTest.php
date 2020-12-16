<?php

declare(strict_types=1);

namespace iggyvolz\phlum\test;

use ReflectionClass;
use PHPUnit\Framework\TestCase;
use iggyvolz\phlum\test\BasicTestCase;
use iggyvolz\phlum\helpers\HelperGenerator;
use iggyvolz\phlum\test\BasicTestCase_phlum;

class BasicTest extends TestCase
{
    public function testHelperExists(): void
    {
        $this->assertTrue(trait_exists(BasicTestCase_phlum::class));
    }
    public function testHelperHasGetMethod(): void
    {
        $refl=new ReflectionClass(BasicTestCase::class);
        $this->assertTrue($refl->hasMethod("getFoo"));
        $method = $refl->getMethod("getFoo");
        // Check casing of method name
        $this->assertSame($method->getName(), "getFoo");
    }
    public function testHelperHasSetMethod(): void
    {
        $refl=new ReflectionClass(BasicTestCase::class);
        $this->assertTrue($refl->hasMethod("setFoo"));
        $method = $refl->getMethod("setFoo");
        // Check casing of method name
        $this->assertSame($method->getName(), "setFoo");
    }
}
