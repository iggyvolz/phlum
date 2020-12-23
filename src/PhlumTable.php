<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use WeakMap;
use ReflectionClass;
use RuntimeException;
use ReflectionProperty;
use ReflectionAttribute;
use iggyvolz\phlum\PhlumDatabase;
use wapmorgan\BinaryStream\BinaryStream;
use iggyvolz\phlum\Attributes\Properties\PhlumProperty;

abstract class PhlumTable
{
    protected ?int $id=null;
    public function getId(): int
    {
        if(is_null($this->id)) {
            throw new RuntimeException("Attempting to get ID of new element");
        }
        return $this->id;
    }
    final public function __construct(private PhlumDatabase $db) {}
    /**
     * @return list<ReflectionProperty>
     */
    public static function getReflectionProperties(): array
    {
        $refl = new ReflectionClass(static::class);
        $props = $refl->getProperties();
        return array_filter($props, fn(ReflectionProperty $rp):bool => !empty($rp->getAttributes(PhlumProperty::class, ReflectionAttribute::IS_INSTANCEOF)));
    }
    /**
     * @return list<PhlumProperty>
     */
    public static function getProperties(): array
    {
        $props = static::getReflectionProperties();
        $attributes = array_map(fn(ReflectionProperty $rp):PhlumProperty => $rp->getAttributes(PhlumProperty::class, ReflectionAttribute::IS_INSTANCEOF)[0]->newInstance(), $props);
        return $attributes;
    }
    public static function getRowWidth(): int
    {
        return array_sum(array_map(fn(PhlumProperty $prop): int => $prop->getWidth(), static::getProperties()));
    }
    private static ?WeakMap $streams = null;
    private static function getStream(PhlumDatabase $db): BinaryStream
    {
        if(is_null(self::$streams)) {
            self::$streams = new WeakMap();
        }
        $streams = self::$streams[$db] ?? [];
        if(!array_key_exists(static::class, $streams)) {
            $file = $db->getDataDir() . "/" . static::getFileName();
            if(!file_exists($file)) {
                $bs = new BinaryStream($file, BinaryStream::CREATE);
                $bs->writeInteger(0, 64);
            }
            $streams[static::class] = new BinaryStream($file, BinaryStream::REWRITE);
            self::$streams[$db] = $streams;
        }
        return $streams[static::class];
    }
    private static function getFileName(): string
    {
        return hash("sha256", static::class);
    }
    public static function count(PhlumDatabase $db):int
    {
        $stream = static::getStream($db);
        $stream->go(0);
        return $stream->readInteger(64);
    }
    public static function read(PhlumDatabase $db, int $id):static
    {
        $stream = static::getStream($db);
        $stream->go(8 + $id * static::getRowWidth());
        $self = new static($db);
        foreach(static::getProperties() as $i => $phlumprop) {
            static::getReflectionProperties()[$i]->setValue($self, $phlumprop->read($stream));
        }
        return $self;
    }
    public function write():void
    {
        $stream = static::getStream($this->db);
        if(!is_null($this->id)) {
            $stream->go(8 + $this->id * static::getRowWidth());
        } else {
            // Go to end of stream
            $stream->go(0);
            $newId = $stream->readInteger(64);
            $stream->go(0);
            $stream->writeInteger($newId + 1, 64);
            $stream->go(8 + $newId * static::getRowWidth());

        }
        foreach(static::getProperties() as $i => $phlumprop) {
            $value = static::getReflectionProperties()[$i]->getValue($this);
            $phlumprop->write($stream, $value);
        }
        if(is_null($this->id)) {
            $this->id = $newId;
        }
    }
}