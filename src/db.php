<?php
function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $envPath = dirname(__DIR__) . '/.env.php';
    if (!file_exists($envPath)) {
        throw new RuntimeException('Missing .env.php. Copy .env.php.example to .env.php and configure.');
    }
    $env = require $envPath;
    $db = $env['db'];

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['database'],
        $db['charset']
    );

    $pdo = new PDO($dsn, $db['username'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
