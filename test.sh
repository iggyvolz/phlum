#!/usr/bin/env bash
php -d auto_prepend_file=vendor/autoload.php genhelpers.php test/
vendor/bin/phpunit