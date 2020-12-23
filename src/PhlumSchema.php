<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use ReflectionClass;
use ReflectionProperty;
use wapmorgan\BinaryStream\BinaryStream;
use iggyvolz\Initializable\Initializable;
use iggyvolz\phlum\Attributes\Description;
use iggyvolz\phlum\Attributes\Properties\PhlumProperty;
use iggyvolz\phlum\Attributes\Properties\VarallocProperty;

#[Description("Represents schema as it should be stored by Phlum")]
abstract class PhlumSchema implements Initializable
{
    final public function __construct()
    {
    }


    /**
     * @var array<class-string,PhlumProperty[]>
     */
    private $properties=[];
    public static function init(): void
    {
        if(!array_key_exists(static::class, self::$properties)) {
            self::$properties[static::class] = array_map(fn(ReflectionProperty $rp):PhlumProperty => 
                $rp->getAttributes(PhlumProperty::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? self::getDefaultProperty($rp)
            , (new ReflectionClass(static::class))->getProperties());
        }
    }
    
    private static function getDefaultProperty(ReflectionProperty $rp): PhlumProperty
    {
        throw new RuntimeException("Not yet implemented");
    }

    /**
     * @return PhlumProperty[]
     */
    final public static function getProperties(): array
    {
        static::init();
        return self::$properties[static::class];
    }

    #[Description("Gets the width of a single row")]
    final public static function getWidth(): int
    {
        return array_sum(array_map(fn(PhlumProperty $p):int => $p->getWidth(), static::getProperties()));
    }
    #[Description("Whether the row requires variable allocation")]
    final public static function hasVarAlloc(): bool
    {
        foreach(static::getProperties() as $p) {
            if($p instanceof VarallocProperty) return true;
        }
        return false;
    }
    #[Description("Gets the filename that should be used for storing the table")]
    final public static function getName(): string
    {
        return base64_encode(self::class);
    }

    final public static function open(string $dir): BinaryStream
    {
        $file = $dir . $this->getName();
        return new BinaryStream($file, file_exists($file) ? BinaryStream::REWRITE : BinaryStream::CREATE);
    }
}
