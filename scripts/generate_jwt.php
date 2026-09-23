<?php

$dir = dirname(__DIR__) . '/config/jwt';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$passphrase = 'c9d7f9c7068cf0bfeada0c721da8fa22a2dea01a4073c19b7c24f0caa1aee7ad';

$cnfPath = 'C:/tools/php8.3/extras/ssl/openssl.cnf';
if (!file_exists($cnfPath)) {
    $cnfPath = 'C:/xampp/apache/conf/openssl.cnf';
}

$config = [
    'config' => $cnfPath,
    'private_key_bits' => 4096,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];

$res = openssl_pkey_new($config);
if (!$res) {
    echo "OpenSSL error: " . openssl_error_string() . "\n";
    exit(1);
}

openssl_pkey_export($res, $privKey, $passphrase, $config);
file_put_contents($dir . '/private.pem', $privKey);

$details = openssl_pkey_get_details($res);
file_put_contents($dir . '/public.pem', $details['key']);

echo "JWT keys created successfully in $dir\n";
