<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

//use JetBrains\PhpStorm\ArrayShape;
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
        $attrs = (new ReflectionClass($this->class))->getAttributes(
            TableReference::class,
            ReflectionAttribute::IS_INSTANCEOF
        );
        if (empty($attrs)) {
            throw new \LogicException("No TableReference specified on " . $this->class);
        }
        /**
         * @var TableReference $tableRef
         */
        $tableRef = $attrs[0]->newInstance();
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
        // Add getAll method
        $getAll = $trait->addMethod("getAll")->setPublic()->setStatic()->setReturnType("array");
        $getAll->addParameter("driver")->setType(PhlumDriver::class);
        $getAll->addBody(
            "return array_map(fn(\\$schema \$val): static => \$val->getPhlumObject(fn(\\$schema \$schema)" .
            " => new self(\$schema)), \\$schema::getMany(\$driver, ["
        );
        $getAll->addParameter("condition")->setType('array')->setDefaultValue([]);
//        $arrayShape = [];
        // Add updateAll method
        $updateAll = $trait->addMethod("updateAll")->setPublic()->setStatic()->setReturnType("void");
        $updateAll->addParameter("driver")->setType(PhlumDriver::class);
        $updateAll->addParameter("condition")->setType('array')->setDefaultValue([]);
        $updateAll->addParameter("data")->setType('array')->setDefaultValue([]);
        $updateAll->addBody('$_condition = [];');
        $updateAll->addBody('$_data = [];');
        // Add getters and setters for properties
        foreach ($schema::getProperties() as $i => $property) {
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
            $create->addBody("    \$$propertyName,");
//            $arrayShape[$propertyName] = Condition::class;
            $getAll->addBody("    \$condition['$propertyName'] ?? null,");
            $updateAll->addBody("\$_condition[] = \$condition['$propertyName'] ?? null;");
            $updateAll->addBody("if(array_key_exists('$propertyName', \$data)) \$_data[$i] = \$data['$propertyName'];");
        }
//        $getAllParam->addAttribute(ArrayShape::class, [$arrayShape]);
        $getAll->addBody("]));");
        $create->addBody("])->getPhlumObject(fn(\\$schema \$schema) => new self(\$schema));");
        $updateAll->addBody("\\$schema::updateMany(\$driver, \$_condition, \$_data);");
        return $file;
    }

    private static function getTypeName(?ReflectionType $type): string
    {
        if (is_null($type)) {
            throw new \RuntimeException("Illegal untyped property");
        }
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }
        if ($type instanceof \ReflectionUnionType) {
            return implode("|", array_map(fn(ReflectionType $t): string => self::getTypeName($t), $type->getTypes()));
        }
        throw new \LogicException("Unknown reflection type");
    }

    /**
     * @phan-suppress PhanPossiblyFalseTypeReturn
     *  -> https://github.com/phan/phan/issues/4335
     */
    public function __toString(): string
    {
        return substr((new PsrPrinter())->printFile($this->contents), strlen("<?php\n"));
    }
}
