<?php

declare(strict_types=1);

namespace iggyvolz\phlum\test;

use ReflectionClass;
use ReflectionNamedType;
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
        $this->assertSame("getFoo", $method->getName());
        // Check return type of method
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        $this->assertSame("int", $returnType->getName());
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isFinal());
        $this->assertFalse($method->isStatic());
    }

    public function testHelperHasSetMethod(): void
    {
        $refl=new ReflectionClass(BasicTestCase::class);
        $this->assertTrue($refl->hasMethod("setFoo"));
        $method = $refl->getMethod("setFoo");
        // Check casing of method name
        $this->assertSame($method->getName(), "setFoo");
        // Check return type of method
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        $this->assertSame("int", $returnType->getName());
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isFinal());
        $this->assertFalse($method->isStatic());
    }

    public function testCustomAccessGetMethod(): void
    {
        $refl=new ReflectionClass(BasicTestCase::class);
        $this->assertTrue($refl->hasMethod("getProtectedReadOnly"));
        $method = $refl->getMethod("getProtectedReadOnly");
        // Check casing of method name
        $this->assertSame("getProtectedReadOnly", $method->getName());
        // Check return type of method
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        $this->assertSame("int", $returnType->getName());
        $this->assertTrue($method->isProtected());
        $this->assertTrue($method->isFinal());
        $this->assertFalse($method->isStatic());
    }
    public function testCustomAccessSetMethod(): void
    {
        $refl=new ReflectionClass(BasicTestCase::class);
        $this->assertTrue($refl->hasMethod("setProtectedReadOnly"));
        $method = $refl->getMethod("setProtectedReadOnly");
        // Check casing of method name
        $this->assertSame($method->getName(), "setProtectedReadOnly");
        // Check return type of method
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        $this->assertSame("int", $returnType->getName());
        $this->assertTrue($method->isPrivate());
        $this->assertFalse($method->isStatic());
    }
}
