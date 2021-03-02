#!/usr/bin/env bash
php -d auto_prepend_file=vendor/autoload.php genhelpers.php test/
XDEBUG_MODE=coverage vendor/bin/phpunit