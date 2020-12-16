<?php
declare(strict_types=1);

use iggyvolz\phlum\PhlumObject;
use iggyvolz\phlum\helpers\HelperGenerator;

require_once __DIR__ . "/vendor/autoload.php";

spl_autoload_register(function(string $class) {
    if(str_ends_with($class, "_phlum")) {
        // Generate stub so that PHP can compile the main class
        HelperGenerator::generateStubs(substr($class, 0, -strlen("_phlum")));
    }
});

// Require all PHP files
foreach([__DIR__ . "/src", __DIR__ . "/test"] as $dir) {
    $directory = new RecursiveDirectoryIterator($dir);
    $iterator = new RecursiveIteratorIterator($directory);
    $regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
    foreach(array_keys(iterator_to_array($regex)) as $file) {
        require_once $file;
    }
}
HelperGenerator::generateHelpers(...array_filter(get_declared_classes(), fn(string $c):bool => 
    is_subclass_of($c, PhlumObject::class)
));
