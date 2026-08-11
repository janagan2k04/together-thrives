<?php
require __DIR__ . '/constants.php';

$env = [];
if (is_file(BASE_PATH . '/.env')) {
    $env = parse_ini_file(BASE_PATH . '/.env') ?: [];
}
$get = fn(string $k, $d = null) => $env[$k] ?? $d;

return [
    'name'     => 'TogetherThrives',
    'base_url' => rtrim($get('BASE_URL', 'http://localhost/togetherthrives/public'), '/'),
    'debug'    => filter_var($get('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOL),
    'timezone' => 'Asia/Colombo',

    'default_locale' => 'en',
    'locales'        => ['en', 'si', 'ta'],

    'db' => [
        'host'    => $get('DB_HOST', '127.0.0.1'),
        'port'    => $get('DB_PORT', '3306'),
        'name'    => $get('DB_NAME', 'togetherthrives'),
        'user'    => $get('DB_USER', 'root'),
        'pass'    => $get('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],

    'mail' => [
        'host'      => $get('MAIL_HOST', 'smtp.gmail.com'),
        'port'      => (int) $get('MAIL_PORT', 587),
        'user'      => $get('MAIL_USER', ''),
        'pass'      => $get('MAIL_PASS', ''),
        'from'      => $get('MAIL_FROM', 'noreply@togetherthrives.lk'),
        'from_name' => 'TogetherThrives',
    ],

    'upload' => [
        'max_mb' => 5,
        'images' => ['jpg', 'png', 'webp'],
        'docs'   => ['pdf', 'jpg', 'png'],
    ],

    'websocket' => [
        'host' => $get('WS_HOST', '127.0.0.1'),
        'port' => (int) $get('WS_PORT', 8080),
    ],
];
