<?php
header('Content-Type: application/json');

$keys = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'];
$status = [];

foreach ($keys as $key) {
    $val = getenv($key);
    $status[$key] = [
        'getenv' => ($val !== false),
        'env_global' => isset($_ENV[$key]),
        'server_global' => isset($_SERVER[$key]),
        'value' => ($val !== false) ? (($key === 'DB_PASS') ? '***' : $val) : null
    ];
}

$status['PHP_OS'] = PHP_OS;
$status['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'N/A';

echo json_encode($status);
