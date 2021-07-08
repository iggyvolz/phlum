#!/usr/bin/env bash
php -r 'require_once __DIR__ . "/vendor/autoload.php"; iggyvolz\phlum\HelperGeneratorFactory::generateHelpers(__DIR__."/test");' && XDEBUG_MODE=coverage vendor/bin/phpunit