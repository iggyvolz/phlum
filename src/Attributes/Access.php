<?php

declare(strict_types=1);

namespace iggyvolz\phlum\Attributes;

use Attribute;
use RuntimeException;
use ReflectionProperty;
use ReflectionAttribute;
use Nette\PhpGenerator\Method;

#[Attribute]
#[Description("Controls access for a Phlum property")]
class Access
{
    public const PUBLIC = 1;
    public const PROTECTED = 2;
    public const PRIVATE = 3;
    public function __construct(public int $read, public int $write)
    {
    }
    #[Description("Gets a default access level given a Phlum property")]
    private static function getDefault(ReflectionProperty $property): self
    {
        $accessLevel = match (true) {
            $property->isPublic() => self::PUBLIC,
            $property->isProtected() => self::PROTECTED,
            $property->isPrivate() => self::PRIVATE,
        };
        return new self($accessLevel, $accessLevel);
    }
    #[Description("Gets the access level for a Phlum property")]
    public static function get(ReflectionProperty $property): self
    {
        $attributes = $property->getAttributes(self::class, ReflectionAttribute::IS_INSTANCEOF);
        if (empty($attributes)) {
            return self::getDefault($property);
        } else {
            return $attributes[0]->newInstance();
        }
    }
    public function applyGetter(Method $method): void
    {
        self::apply($method, $this->read);
    }
    public function applySetter(Method $method): void
    {
        self::apply($method, $this->write);
    }
    private static function apply(Method $method, int $accessLevel): void
    {
        switch ($accessLevel) {
            case self::PUBLIC:
                $method->setPublic();
                $method->setFinal();
                break;
            case self::PROTECTED:
                $method->setProtected();
                $method->setFinal();
                break;
            case self::PRIVATE:
                $method->setPrivate();
                break;
            default:
                throw new RuntimeException("Illegal access level " . $accessLevel . " for method");
        }
    }
}
