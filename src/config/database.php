<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

/** Única conexión PDO a MySQL (prepared statements, sin emulación). */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env('DB_HOST', '127.0.0.1');
    $puerto = env('DB_PORT', '3306');
    $base = env('DB_NAME', 'changosgames');
    $dsn = "mysql:host={$host};port={$puerto};dbname={$base};charset=utf8mb4";

    $pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
