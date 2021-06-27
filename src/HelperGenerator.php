<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

//use JetBrains\PhpStorm\ArrayShape;
use iggyvolz\phlum\Indeces\InclusionIndex;
use iggyvolz\phlum\Indeces\Index;
use iggyvolz\phlum\Indeces\UniqueSearchIndex;
use iggyvolz\phlum\Indeces\SearchIndex;
use Iggyvolz\SimpleAttributeReflection\AttributeReflection;
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
        $constructor = $trait->addMethod("__construct")->setPrivate();
        $tableRef = AttributeReflection::getAttribute(
            new ReflectionClass($this->class),
            TableReference::class
        )
            ?? throw new \LogicException("No TableReference specified on " . $this->class);
        $schema = $tableRef->table;
        if (!is_subclass_of($schema, PhlumTable::class)) {
            throw new \LogicException("Invalid schema $schema");
        }
        $constructor->addPromotedParameter("schema")->setType($schema)->setProtected();
        // Add create method
        $create = $trait->addMethod("create")->setPublic()->setStatic()->setReturnType("static");
        $create->addParameter("driver")->setType(PhlumDriver::class);
        $create->addBody("return \\$schema::create(\$driver, [");
        // Add get method
        $get = $trait->addMethod("get")->setPublic()->setStatic()->setReturnType("static");
        $get->addParameter("driver")->setType(PhlumDriver::class);
        $get->addParameter("id")->setType("int");
        $get->addBody(
            "return \\$schema::get(\$driver, \$id)->getPhlumObject(fn(\\$schema \$schema) => new self(\$schema));"
        );
        // Add getId method
        $getId = $trait->addMethod("getId")->setPublic()->setReturnType("int");
        $getId->addBody("return \$this->schema->getId();");
        // Add getters and setters for properties
        foreach ($schema::getProperties() as $property) {
            $propertyName = $property->getName();
            $upperPropertyName = ucfirst($propertyName);
            $propertyType = self::getTypeName($property->getType());
            if ($attr = AttributeReflection::getAttribute($property, PhlumObjectReference::class)) {
                $propertyType = $attr->class;
            }
            $access = Access::get($property);
            $getter = $trait->addMethod("get$upperPropertyName");
            $setter = $trait->addMethod("set$upperPropertyName")->setReturnType("void");
            $getter->setReturnType($propertyType);
            if (AttributeReflection::getAttribute($property, PhlumObjectReference::class)) {
                $getter->setBody("return \\$propertyType::get(\$this->schema->getDriver(),"
                    . " \$this->schema->$propertyName);");
            } else {
                $getter->setBody("return \$this->schema->$propertyName;");
            }
            $access->applyGetter($getter);
            $setter->addParameter("val")->setType($propertyType);
            $access->applySetter($setter);
            if (AttributeReflection::getAttribute($property, PhlumObjectReference::class)) {
                $setter->setBody("\$this->schema->$propertyName = \$val->getId();\n\$this->schema->write();");
            } else {
                $setter->setBody("\$this->schema->$propertyName = \$val;\n\$this->schema->write();");
            }
            $parameter = $create->addParameter($propertyName)->setType($propertyType);
            if($property->hasDefaultValue()) {
                $parameter->setDefaultValue($property->getDefaultValue());
            }
            if (AttributeReflection::getAttribute($property, PhlumObjectReference::class)) {
                $create->addBody("    " . var_export($propertyName, true) . " => \${$propertyName}->getId(),");
            } else {
                $create->addBody("    " . var_export($propertyName, true) . " => \$$propertyName,");
            }
        }
        $create->addBody("])->getPhlumObject(fn(\\$schema \$schema) => new self(\$schema));");

        // Add indeces
        foreach ([new ReflectionClass($schema), ...(new ReflectionClass($schema))->getProperties()] as $target) {
            foreach ($target->getAttributes(Index::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                $index = $attr->newInstance();
                $methodName = $index->getMethodName($target);
                $idMethodName = ($index instanceof UniqueSearchIndex) ? "{$methodName}Id" : "{$methodName}Ids";
                $method = $trait->addMethod($methodName);
                $idMethod = $trait->addMethod($idMethodName);
                switch (true) {
                    case $index instanceof InclusionIndex:
                        $method->setReturnType("array")->setStatic();
                        $method->addParameter("driver")->setType(PhlumDriver::class);
                        $method->setBody(
                            "return array_map(fn(int \$id): self => self::get(\$driver, \$id)," .
                            " self::$idMethodName(\$driver));"
                        );
                        $idMethod->setReturnType("array")->setStatic();
                        $idMethod->addParameter("driver")->setType(PhlumDriver::class);
                        $idMethod->setBody("return (new \\" . $attr->getName() . "(" . implode(
                            ",",
                            array_map(fn(mixed $arg): string => var_export($arg, true), $attr->getArguments())
                        ) . "))->get(new \ReflectionClass(\\$schema::class), \$driver);");
                        break;
                    case $index instanceof SearchIndex:
                        $method->setReturnType("array")->setStatic();
                        $method->addParameter("driver")->setType(PhlumDriver::class);
                        $method->addParameter("input")->setType($index->getType($target));
                        $method->setBody("return array_map(fn(int \$id): self => self::get(\$driver, \$id)," .
                            " self::$idMethodName(\$driver, \$input));");
                        $idMethod->setReturnType("array")->setStatic();
                        $idMethod->addParameter("driver")->setType(PhlumDriver::class);
                        $idMethod->addParameter("input")->setType($index->getType($target));
                        $idMethod->setBody("return (new \\" . $attr->getName() . "(" . implode(
                            ",",
                            array_map(fn(mixed $arg): string => var_export($arg, true), $attr->getArguments())
                        ) . "))->get(new \ReflectionProperty(\\$schema::class, "
                            . var_export($target->getName(), true) . "), \$driver, \$input);");
                        break;
                    case $index instanceof UniqueSearchIndex:
                        $method->setReturnType("self|null")->setStatic();
                        $method->addParameter("driver")->setType(PhlumDriver::class);
                        $method->addParameter("input")->setType($index->getType($target));
                        $method->setBody(
                            "\$obj = self::$idMethodName(\$driver, \$input);"
                            . " if(is_null(\$obj)) return null; return self::get(\$driver, \$obj);"
                        );
                        $idMethod->setReturnType("null|int")->setStatic();
                        $idMethod->addParameter("driver")->setType(PhlumDriver::class);
                        $idMethod->addParameter("input")->setType($index->getType($target));
                        $idMethod->setBody("return (new \\" . $attr->getName() . "(" . implode(
                            ",",
                            array_map(fn(mixed $arg): string => var_export($arg, true), $attr->getArguments())
                        ) . "))->get(new \ReflectionProperty(\\$schema::class, "
                            . var_export($target->getName(), true) . "), \$driver, \$input);");
                        break;
                    default:
                        throw new \TypeError("Unknown index type " . get_debug_type($index));
                }
            }
        }
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
