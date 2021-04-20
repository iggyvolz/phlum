<?php

declare(strict_types=1);

namespace iggyvolz\phlum;

use iggyvolz\lmdb\Environment;
use iggyvolz\lmdb\LMDB;
use Ramsey\Uuid\Lazy\LazyUuidFromString;
use Ramsey\Uuid\Nonstandard\UuidV6;
use Ramsey\Uuid\Provider\Node\StaticNodeProvider;
use Ramsey\Uuid\Rfc4122\Fields;
use Ramsey\Uuid\Rfc4122\FieldsInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class PhlumDatabase
{
    private Environment $lmdb;
    private UuidInterface $uuid;
    /**
     * @var array<string,self> Array of registered databases
     */
    private static array $databases = [];

    /**
     * PhlumDatabase constructor.
     * @param string $directory Directory to store the database in
     * @param list<class-string<PhlumTable>> $classes Classes that are registered to the database
     */
    public function __construct(string $directory, private array $classes)
    {
        $this->lmdb = new Environment($directory, numDatabases:count($classes) + 1);
        // Read UUID from environment
        $uuidString = $this->lmdb->newTransaction(true)->getHandle("meta")->get("uuid");
        if (is_null($uuidString)) {
            throw new \RuntimeException("Invalid database selected");
        }
        $this->uuid = Uuid::fromBytes($uuidString);
        $this->register();
    }

    /**
     * @var array<class-string<PhlumTable>,StaticNodeProvider>
     */
    private array $nodeProviders = [];

    /**
     * @param class-string<PhlumTable> $class
     */
    public function getNodeProvider(string $class): StaticNodeProvider
    {
        return $this->nodeProviders[$class] ??= $this->realGetNodeProvider($class);
    }

    /**
     * @param class-string<PhlumTable> $class
     */
    private function realGetNodeProvider(string $class): StaticNodeProvider
    {
        return new StaticNodeProvider(new Hexadecimal(
            substr(self::sha256($this->uuid->getBytes() . $class), 0, 12)
        ));
    }
    private static function sha256(string $data): string
    {
        return hash("sha256", $data);
    }

    /**
     * Register this database globally
     */
    private function register(): void
    {
        foreach ($this->classes as $class) {
            self::$databases[$db = $this->getNodeProvider($class)->getNode()->toString()] = $this;
        }
    }

    public static function getDatabase(string $node): ?self
    {
        return self::$databases[$node] ?? null;
    }

    public static function initialize(string $directory, array $classes, UuidInterface $uuid = null): self
    {
        $uuid ??= Uuid::uuid4();
        $lmdb = new Environment($directory, numDatabases: count($classes)  + 1);
        $transaction = $lmdb->newTransaction(false);
        $transaction->getHandle("meta", LMDB::CREATE)->put("uuid", $uuid->getBytes());
        $transaction->commit();
        $self = new self($directory, $classes);
        foreach ($classes as $class) {
            $transaction = $lmdb->newTransaction(false);
            $transaction->getHandle($self->getNodeProvider($class)->getNode()->toString(), LMDB::CREATE);
            $transaction->commit();
        }
        return $self;
    }

    /**
     * Unregister this database globally
     */
    public function unregister(): void
    {
        foreach (array_keys(self::$databases) as $key) {
            if (self::$databases[$key] === $this) {
                unset(self::$databases[$key]);
            }
        }
    }
    // TODO maybe replace this with https://github.com/igbinary/igbinary
    private function serialize(mixed $value): string
    {
        return serialize($value);
    }

    private function unserialize(string $data): mixed
    {
        return unserialize($data);
    }

    public function get(UuidInterface $id): ?PhlumTable
    {
        $fields = $id->getFields();
        if (!$fields instanceof Fields) {
            throw new \LogicException();
        }
        $key = substr($id->getBytes(), 0, 11); // Last 5 characters is the node
        $serialized = $this->lmdb->newTransaction(true)->getHandle($fields->getNode()->toString())->get($key);
        if (is_null($serialized)) {
            return null;
        }
        $unserialized = $this->unserialize($serialized);
        if (!$unserialized instanceof PhlumTable) {
            return null;
        }
        return $unserialized;
    }

    /**
     * @param class-string<PhlumTable> $class
     * @return UuidV6
     */
    public function getUuid(string $class): UuidV6
    {
        $nodeProvider = $this->getNodeProvider($class);
        $uuid = Uuid::uuid6($nodeProvider->getNode());
        if ($uuid instanceof LazyUuidFromString) {
            $uuid = $uuid->toUuidV6();
        }
        if (!$uuid instanceof UuidV6) {
            throw new \LogicException();
        }
        return $uuid;
    }

    public function create(PhlumTable $object): void
    {
        $data = $this->serialize($object);
        $key = substr($object->getId()->getBytes(), 0, 11); // Last 5 characters is the node
        $transaction = $this->lmdb->newTransaction(false);
        $fields = $object->getId()->getFields();
        if(!$fields instanceof FieldsInterface) {
            throw new \LogicException();
        }
        $transaction->getHandle($fields->getNode()->toString(), LMDB::CREATE)->put($key, $data);
        $transaction->commit();
    }

    public function update(PhlumTable $object): void
    {
        $data = $this->serialize($object);
        $nodeProvider = $this->getNodeProvider(get_class($object));
        $uuid = $object->getId();
        $key = substr($uuid->getBytes(), 0, 11); // Last 5 characters is the node
        $transaction = $this->lmdb->newTransaction(false);
        $transaction->getHandle($nodeProvider->getNode()->toString())->put($key, $data);
        $transaction->commit();
    }

    /**
     * @param StaticNodeProvider $nodeProvider
     * @return \Generator<PhlumTable>
     */
    public function getAll(StaticNodeProvider $nodeProvider): \Generator
    {
        $handle = $this->lmdb->newTransaction(true)->getHandle($nodeProvider->getNode()->toString());
        /**
         * @var string $serialized
         */
        foreach ($handle->all() as $serialized) {
            /**
             * @var mixed
             */
            $unserialized = $this->unserialize($serialized);
            if (!$unserialized instanceof PhlumTable) {
                continue;
            }
            yield $unserialized;
        }
    }
}
