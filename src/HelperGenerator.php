<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use iggyvolz\phlum\Attributes\CreateParameterPromoted;
use iggyvolz\phlum\Attributes\GetterPromoted;
use iggyvolz\phlum\Attributes\SetterParameterPromoted;
use iggyvolz\phlum\Attributes\SetterPromoted;
use iggyvolz\phlum\Indeces\InclusionIndex;
use iggyvolz\phlum\Indeces\Index;
use iggyvolz\phlum\Indeces\UniqueSearchIndex;
use iggyvolz\phlum\Indeces\SearchIndex;
use Iggyvolz\SimpleAttributeReflection\AttributeReflection;
use Nette\PhpGenerator\Parameter;
use ReflectionClass;
use ReflectionAttribute;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use iggyvolz\phlum\Attributes\Access;
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
        $schema = $this->class . "Table";
        if (!is_subclass_of($schema, PhlumTable::class)) {
            throw new \LogicException("Invalid schema $schema");
        }
        $constructor->addPromotedParameter("schema")->setType($schema)->setProtected();
        $constructor->addBody("parent::__construct(\$schema);");
        // Add create method
        $create = $trait->addMethod("create")->setPublic()->setStatic()->setReturnType("static");
        $create->addParameter("driver")->setType(PhlumDriver::class);
        $create->addBody("return (new \\$schema(\$driver))->create([");
        // Add getters and setters for properties
        foreach ((new ReflectionClass($schema))->getProperties() as $property) {
            if($property->getDeclaringClass()->getName() !== $schema) continue;
            $propertyName = $property->getName();
            $upperPropertyName = ucfirst($propertyName);
            $propertyType = self::getTypeName($property->getType());
            $access = Access::get($property);
            $getter = $trait->addMethod("get$upperPropertyName");
            $setter = $trait->addMethod("set$upperPropertyName")->setReturnType("void");
            $getter->setReturnType($propertyType);
            $getter->setBody("return \$this->schema->$propertyName;");
            $access->applyGetter($getter);
            $setterParameter = $setter->addParameter("val")->setType($propertyType);
            $access->applySetter($setter);
            $setter->setBody("\$this->schema->$propertyName = \$val;\n\$this->schema->write();");
            $createParameter = $create->addParameter($propertyName)->setType($propertyType);
            if ($property->hasDefaultValue()) {
                $createParameter->setDefaultValue($property->getDefaultValue());
            }
            $create->addBody("    " . var_export($propertyName, true) . " => \$$propertyName,");
            foreach(AttributeReflection::getAttributes($property, CreateParameterPromoted::class) as $attribute) {
                $createParameter->addAttribute(...$attribute->getCreateParameterAttribute());
            }
            foreach(AttributeReflection::getAttributes($property, SetterParameterPromoted::class) as $attribute) {
                $setterParameter->addAttribute(...$attribute->getSetterParameterAttribute());
            }
            foreach(AttributeReflection::getAttributes($property, SetterPromoted::class) as $attribute) {
                $setter->addAttribute(...$attribute->getSetterAttribute());
            }
            foreach(AttributeReflection::getAttributes($property, GetterPromoted::class) as $attribute) {
                $getter->addAttribute(...$attribute->getGetterAttribute());
            }
        }
        // Sort parameters by required first then optional
        $parameters = $create->getParameters();
        usort(
            $parameters,
            fn(Parameter $p1, Parameter $p2): int =>
                ($p1->hasDefaultValue() ? 1 : 0) <=> ($p2->hasDefaultValue() ? 1 : 0)
        );
        $create->setParameters($parameters);
        $create->addBody("])->getPhlumObject(fn(\\$schema \$schema) => new self(\$schema));");

        // Add indeces
        foreach ([new ReflectionClass($schema), ...(new ReflectionClass($schema))->getProperties()] as $target) {
            foreach ($target->getAttributes(Index::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                $index = $attr->newInstance();
                $methodName = $index->getMethodName($target);
                $method = $trait->addMethod($methodName);
                switch (true) {
                    case $index instanceof InclusionIndex:
                        $method->setReturnType("array")->setStatic();
                        $method->addParameter("driver")->setType(PhlumDriver::class);
                        $method->setBody("return (new \\" . $attr->getName() . "(" . implode(
                            ",",
                            array_map(fn(mixed $arg): string => var_export($arg, true), $attr->getArguments())
                        ) . "))->get(new \ReflectionClass(\\$schema::class), \$driver);");
                        break;
                    case $index instanceof SearchIndex:
                        $method->setReturnType("array")->setStatic();
                        $method->addParameter("driver")->setType(PhlumDriver::class);
                        $method->addParameter("input")->setType($index->getType($target));
                        $method->setBody("return (new \\" . $attr->getName() . "(" . implode(
                            ",",
                            array_map(fn(mixed $arg): string => var_export($arg, true), $attr->getArguments())
                        ) . "))->get(new \ReflectionProperty(\\$schema::class, "
                            . var_export($target->getName(), true) . "), \$driver, \$input);");
                        break;
                    case $index instanceof UniqueSearchIndex:
                        $method->setReturnType(PhlumObjectReference::class . "|null")->setStatic();
                        $method->addParameter("driver")->setType(PhlumDriver::class);
                        $method->addParameter("input")->setType($index->getType($target));
                        $method->setBody("return (new \\" . $attr->getName() . "(" . implode(
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
