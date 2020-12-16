<?php

declare(strict_types=1);

namespace iggyvolz\phlum\helpers;

use ReflectionClass;
use iggyvolz\phlum\PhlumObject;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

class HelperGenerator
{
    private PhpFile $contents;
    /**
     * @psalm-param class-string<PhlumObject> $class
     */
    public function __construct(private string $class, private bool $isStub)
    {
        $this->contents = $this->generateContents();
    }
    /**
     * @psalm-param (class-string<PhlumObject>|ReflectionClass<PhlumObject>)[] $classes
     */
    private static function gen(array $classes, bool $isStub): void
    {
        $classes = array_map(fn(string | ReflectionClass $c): ReflectionClass =>
            $c instanceof ReflectionClass ? $c : new ReflectionClass($c), $classes);
        foreach ($classes as $class) {
            $helper = new self($class->getName(), $isStub);
            file_put_contents(substr($class->getFileName(), 0, -strlen(".php")) . "_phlum.php", $helper->__toString());
        }
    }
    /**
     * @psalm-param (class-string<PhlumObject>|ReflectionClass<PhlumObject>)[] $classes
     */
    public static function generateHelpers(string | ReflectionClass ...$classes): void
    {
        self::gen($classes, false);
    }
    /**
     * @psalm-param (class-string<PhlumObject>|ReflectionClass<PhlumObject>)[] $classes
     */
    public static function generateStubs(string | ReflectionClass ...$classes): void
    {
        self::gen($classes, true);
    }
    private function generateContents(): PhpFile
    {
        $expl = explode("\\", $this->class);
        $classname = array_pop($expl) . "_phlum";
        $namespaceName = implode("\\", $expl);
        $file = new PhpFile();
        $file->setStrictTypes();
        $namespace = $file->addNamespace($namespaceName);
        $trait = $namespace->addClass($classname)->setTrait();
        if($this->isStub) {
            return $file;
        }
        $schema = new ReflectionClass(get_class(($this->class)::getSchema()));
        foreach($schema->getProperties() as $property) {
            $propertyName = $property->getName();
            $upperPropertyName = ucfirst($propertyName);
            $getter = $trait->addMethod("get$upperPropertyName");
        }
        return $file;
    }
    public function __toString(): string
    {
        return (new PsrPrinter())->printFile($this->contents);
    }
}
