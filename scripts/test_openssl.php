<?php
echo "OpenSSL_VERSION=" . (defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'unknown') . PHP_EOL;
echo "OPENSSL_CONF=" . (getenv('OPENSSL_CONF') ?: '') . PHP_EOL;
echo "OS_FAMILY=" . PHP_OS_FAMILY . PHP_EOL;

$fallback = __DIR__ . '/../storage/framework/openssl_fallback.cnf';
if (!is_file($fallback)) {
    @file_put_contents($fallback, implode(PHP_EOL, [
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
putenv('OPENSSL_CONF=' . $fallback);
echo "OPENSSL_CONF_SET_TO_FALLBACK=" . (getenv('OPENSSL_CONF') ?: '') . PHP_EOL;

$args = [
    'private_key_type' => OPENSSL_KEYTYPE_EC,
    'curve_name' => 'prime256v1',
];
$res = @openssl_pkey_new($args);
echo "EC_KEY_CREATED=" . ($res !== false ? 'true' : 'false') . PHP_EOL;
if ($res === false) {
    echo "openssl_error_string=" . (function_exists('openssl_error_string') ? (openssl_error_string() ?: '') : '') . PHP_EOL;
}