<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

//use JetBrains\PhpStorm\ArrayShape;
use Ramsey\Uuid\UuidInterface;
use ReflectionClass;
use ReflectionAttribute;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use iggyvolz\phlum\Attributes\Access;
use iggyvolz\phlum\Attributes\TableReference;
use ReflectionType;
use Stringable;

class HelperGenerator implements Stringable
{
    private PhpFile $contents;
    /**
     * @psalm-param class-string<PhlumObject> $class
     */
    public function __construct(private string $class)
    {
        $this->contents = $this->generateContents();
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
        $constructor = $trait->addMethod("__construct")->setProtected();
        $attrs = (new ReflectionClass($this->class))->getAttributes(
            TableReference::class,
            ReflectionAttribute::IS_INSTANCEOF
        );
        if (empty($attrs)) {
            throw new \LogicException("No TableReference specified on " . $this->class);
        }
        /**
         * @phpstan-var TableReference
         */
        $tableRef = $attrs[0]->newInstance();
        $schema = $tableRef->table;
        if (!is_subclass_of($schema, PhlumTable::class)) {
            throw new \LogicException("Invalid schema $schema");
        }
        $constructor->addPromotedParameter("schema")->setType($schema)->setProtected();
        $constructor->addBody("parent::__construct();");

        // Add getSchema method public abstract function getSchema(): PhlumTable;
        $getSchema = $trait->addMethod("getSchema")->setPublic()->setFinal()->setReturnType(PhlumTable::class);
        $getSchema->addBody('return $this->schema;');

        // Add getId method
        $getId = $trait->addMethod("getId")->setPublic()->setFinal()->setReturnType(UuidInterface::class);
        $getId->addBody('return $this->schema->getId();');
        // Add create method
        $create = $trait->addMethod("create")->setPublic()->setStatic()->setReturnType("static");
        $create->addParameter("db")->setType(PhlumDatabase::class);
        $create->addBody("return \\$schema::create(\$db, [");
        // Add getAll method
        $getAll = $trait->addMethod("getAll")->setPublic()->setStatic()->setReturnType("iterable");
        $getAll->addParameter("db")->setType(PhlumDatabase::class);
        $getAll->addBody(
            "        foreach(\\$schema::getAll(\$db) as \$element) " .
            "yield \$element->getPhlumObject(fn(\\$schema \$schema) => new self(\$schema));"
        );
        // Add __serialize and __unserialize methods
        $serialize = $trait->addMethod("__serialize")->setPublic()->setReturnType("array");
        $serialize->addBody("return \$this->schema->__serialize();");
        $unserialize = $trait->addMethod("__unserialize")->setPublic()->setReturnType("void");
        $unserialize->addParameter("data")->setType("array");
        $unserialize->addBody("\$this->schema->__unserialize(\$data);");
        $unserialize->addBody("\$this->register();");
        // Add getters and setters for properties
        foreach ($schema::getProperties(false) as $property) {
            $propertyName = $property->getName();
            $upperPropertyName = ucfirst($propertyName);
            $propertyType = self::getTypeName($property->getType());
            $access = Access::get($property);
            $getter = $trait->addMethod("get$upperPropertyName");
            $getter->setReturnType($propertyType);
            $getter->setBody("return \$this->schema->$propertyName;");
            $access->applyGetter($getter);
            $setter = $trait->addMethod("set$upperPropertyName");
            $setter->addParameter("val")->setType($propertyType);
            $access->applySetter($setter);
            $setter->setBody("\$this->schema->$propertyName = \$val;\n\$this->schema->write();");
            $create->addParameter($propertyName)->setType($propertyType);
            $create->addBody("    " . var_export($propertyName, true) . " => \$$propertyName,");
        }
        $create->addBody("])->getPhlumObject(fn(\\$schema \$schema) => new self(\$schema));");
        return $file;
    }

    private static function getTypeName(?ReflectionType $type): string
    {
        if (is_null($type)) {
            throw new \RuntimeException("Illegal untyped property");
        }
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName() . ($type->allowsNull() ? "|null" : "");
        }
        if ($type instanceof \ReflectionUnionType) {
            return implode("|", array_map(fn(ReflectionType $t): string => self::getTypeName($t), $type->getTypes()));
        }
        throw new \LogicException("Unknown reflection type");
    }

    public function __toString(): string
    {
        return substr((new PsrPrinter())->printFile($this->contents), strlen("<?php\n"));
    }
}
