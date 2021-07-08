<?php

namespace iggyvolz\phlum;

use iggyvolz\classgen\ClassGenerator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Stringable;

class HelperGeneratorFactory extends ClassGenerator
{

    protected function isValid(string $class): bool
    {
        return str_ends_with($class, "_phlum");
    }

    protected function generate(string $class): string|Stringable
    {
        $parentClass = substr($class, 0, -strlen("_phlum"));
        if (!class_exists($parentClass) || !is_subclass_of($parentClass, PhlumObject::class)) {
            throw new \LogicException("Invalid parent class $parentClass for $class");
        }
        return new HelperGenerator($parentClass);
    }

    public static function generateHelpers(string $dir): void
    {
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
                    public static function get(\\iggyvolz\\phlum\\PhlumObjectReference \$reference): static
                    {
                        throw new \\LogicException("Cannot call method on stub trait");
                    }
                    public function getReference(): \\iggyvolz\\phlum\\PhlumObjectReference
                    {
                        throw new \\LogicException("Cannot call method on stub trait");
                    }
                }
            }
        EOT);
            }
        }, prepend: true);

        $it = new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)), '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
        foreach($it as $f => $_) {
            require_once $f;
        }

        HelperGeneratorFactory::register();
        foreach(get_declared_traits() as $class) {
            if(str_ends_with($class, "_phlum")) {
                // Generate the actual class
                ClassGenerator::autoload($class);
            }
        }

    }
}
