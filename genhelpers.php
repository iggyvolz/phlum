<?php
declare(strict_types=1);

use iggyvolz\phlum\PhlumObject;
use iggyvolz\phlum\helpers\HelperGenerator;

require_once __DIR__ . "/vendor/autoload.php";

spl_autoload_register(function(string $class) {
    if(str_ends_with($class, "_phlum")) {
        // Generate stub so that PHP can compile the main class
        $expl = explode("\\", $class);
        $classname = array_pop($expl);
        $ns = implode("\\", $expl);
        eval("namespace $ns { trait $classname {} }");
    }
});

// Remvoe all phlum files
foreach([__DIR__ . "/src", __DIR__ . "/test"] as $dir) {
    $directory = new RecursiveDirectoryIterator($dir);
    $iterator = new RecursiveIteratorIterator($directory);
    $regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
    foreach(array_keys(iterator_to_array($regex)) as $file) {
        if(str_ends_with($file, "_phlum.php")) {
            unlink($file);
        }
    }
}

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
