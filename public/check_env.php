<?php
// Script temporal para verificar configuración
header('Content-Type: application/json');

$info = [
    'php_version' => phpversion(),
    'pdo_mysql' => extension_loaded('pdo_mysql') ? 'installed' : 'missing',
    'env' => [
        'APP_ENV' => $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'not_set',
        'DATABASE_URL_exists' => isset($_ENV['DATABASE_URL']) || getenv('DATABASE_URL') ? 'yes' : 'no',
        'DATABASE_URL_prefix' => isset($_ENV['DATABASE_URL']) ? substr($_ENV['DATABASE_URL'], 0, 30) . '...' : (getenv('DATABASE_URL') ? substr(getenv('DATABASE_URL'), 0, 30) . '...' : 'NOT SET'),
    ]
];

echo json_encode($info, JSON_PRETTY_PRINT);
