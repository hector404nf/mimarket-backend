<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Configure OpenSSL providers early on Windows to avoid EC key creation issues
if (PHP_OS_FAMILY === 'Windows') {
    $fallbackPath = __DIR__ . '/../storage/framework/openssl_fallback.cnf';
    if (!is_file($fallbackPath)) {
        @file_put_contents($fallbackPath, implode(PHP_EOL, [
            'openssl_conf = openssl_init',
            '',
            '[openssl_init]',
            'providers = providers_sect',
            'alg_section = algorithm_sect',
            '',
            '[providers_sect]',
            'default = default_sect',
            'legacy = legacy_sect',
            '',
            '[default_sect]',
            'activate = 1',
            '',
            '[legacy_sect]',
            'activate = 1',
        ]));
    }
    if (is_file($fallbackPath)) {
        putenv('OPENSSL_CONF=' . $fallbackPath);
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
