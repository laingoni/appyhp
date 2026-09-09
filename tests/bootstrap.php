<?php

$autoload = getenv('APPYHP_TEST_AUTOLOAD') ?: __DIR__ . '/../vendor/autoload.php';
if (! is_file($autoload)) {
    throw new RuntimeException('Run composer install, or set APPYHP_TEST_AUTOLOAD to a Laravel application vendor/autoload.php.');
}
require_once $autoload;

spl_autoload_register(function (string $class): void {
    $prefix = 'Alliswell\\Appyhp\\Tests\\';
    if (str_starts_with($class, $prefix)) {
        require_once __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    }
});
