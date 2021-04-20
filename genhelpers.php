<?php
declare(strict_types=1);

use iggyvolz\phlum\HelperGeneratorFactory;

require_once __DIR__ . "/vendor/autoload.php";

// Add autoloader to generate a stub, so that PHP can compile the main class
spl_autoload_register(function(string $class) {
    if(str_ends_with($class, "_phlum")) {
        $expl = explode("\\", $class);
        $classname = array_pop($expl);
        $ns = implode("\\", $expl);
        eval(<<<EOT
            namespace $ns
            {
                trait $classname
                {
                    public function getId(): \Ramsey\Uuid\UuidInterface { throw new \LogicException(); }
                    public function __serialize(): array { throw new \LogicException(); }
                    public function __unserialize(array \$data): void { throw new \LogicException(); }
                    public static function getAll(\$node): iterable { throw new \LogicException(); }
                    public function getSchema(): \iggyvolz\phlum\PhlumTable { throw new \LogicException(); }
                }
            }
        EOT);
    }
}, prepend: true);

//HelperGeneratorFactory::register();
// Require all classes in chosen directory to force helper generation
$dir = $argv[1];

$it = new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)), '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
foreach($it as $f => $_) {
    require_once $f;
}

HelperGeneratorFactory::register();
foreach(get_declared_traits() as $class) {
    if(str_ends_with($class, "_phlum")) {
        // Generate the actual class
        \iggyvolz\classgen\ClassGenerator::autoload($class);
    }
}
