#!/usr/bin/env bash
php genhelpers.php test/ && XDEBUG_MODE=coverage vendor/bin/phpunit