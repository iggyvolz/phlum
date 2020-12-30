<?php
declare(strict_types=1);

use iggyvolz\phlum\HelperGeneratorFactory;

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
                    public static function get(mixed \$driver, int \$id): static
                    {
                        throw new \\LogicException("Cannot call method on stub trait");
                    }
                    public function getId(): int
                    {
                        throw new \\LogicException("Cannot call method on stub trait");
                    }
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
