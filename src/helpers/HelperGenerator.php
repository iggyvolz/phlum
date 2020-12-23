<?php

declare(strict_types=1);

namespace iggyvolz\phlum\helpers;

use ReflectionClass;
use RuntimeException;
use ReflectionAttribute;
use iggyvolz\phlum\PhlumObject;
use Nette\PhpGenerator\PhpFile;
use iggyvolz\phlum\PhlumDatabase;
use Nette\PhpGenerator\PsrPrinter;
use iggyvolz\phlum\Attributes\Access;
use iggyvolz\phlum\Attributes\TableReference;

class HelperGenerator
{
    private PhpFile $contents;
    /**
     * @psalm-param class-string<PhlumObject> $class
     */
    public function __construct(private string $class)
    {
        $this->contents = $this->generateContents();
    }
    /**
     * @psalm-param (class-string<PhlumObject>|ReflectionClass<PhlumObject>)[] $classes
     */
    public static function generateHelpers(string | ReflectionClass ...$classes): void
    {
        $classes = array_map(fn(string | ReflectionClass $c): ReflectionClass =>
            $c instanceof ReflectionClass ? $c : new ReflectionClass($c), $classes);
        foreach ($classes as $class) {
            $helper = new self($class->getName());
            file_put_contents(substr($class->getFileName(), 0, -strlen(".php")) . "_phlum.php", $helper->__toString());
        }
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
        // Add protected constructor
        $constructor = $trait->addMethod("__construct")->setPrivate();
        $schema = (new ReflectionClass($this->class))->getAttributes(TableReference::class, ReflectionAttribute::IS_INSTANCEOF)[0]->newInstance()->table;
        $constructor->addPromotedParameter("schema")->setType($schema)->setProtected();
        // Add create method
        $create = $trait->addMethod("create")->setPublic()->setStatic()->setReturnType("self");
        $create->addParameter("db")->setType(PhlumDatabase::class);
        $create->addBody("\$self = new self(new \\$schema(\$db));");
        // Add get method
        $get = $trait->addMethod("get")->setPublic()->setStatic()->setReturnType("self");
        $get->addParameter("db")->setType(PhlumDatabase::class);
        $get->addParameter("id")->setType("int");
        $get->addBody("if(\$id >= \\$schema::count(\$db)) {");
        $get->addBody("    throw new \\".RuntimeException::class."('ID out of bounds');");
        $get->addBody("}");
        $get->addBody("return new self(\\$schema::read(\$db, \$id));");

        foreach($schema::getReflectionProperties() as $property) {
            $propertyName = $property->getName();
            $upperPropertyName = ucfirst($propertyName);
            $access = Access::get($property);
            $getter = $trait->addMethod("get$upperPropertyName");
            $getter->setReturnType("int");
            $getter->setBody("return \$this->schema->$propertyName;");
            $access->applyGetter($getter);
            $setter = $trait->addMethod("set$upperPropertyName");
            $setter->setReturnType("int");
            $access->applySetter($setter);
            $setter->setBody("\$self->schema->$propertyName = \$val;\n\$this->schema->write();");
            $create->addParameter($propertyName)->setType($property->getType()->__toString());
            $create->addBody("\$self->schema->$propertyName = \$$propertyName;");
        }
        $create->addBody("\$self->schema->write();");
        $create->addBody("return \$self;");
        return $file;
    }
    public function __toString(): string
    {
        return (new PsrPrinter())->printFile($this->contents);
    }
}
