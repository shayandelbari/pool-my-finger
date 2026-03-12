<?php

function db()
{
    static $pdo;

    if ($pdo) {
        return $pdo;
    }

    $DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
    $DB_PORT = getenv('DB_PORT') ?: '3306';
    $DB_NAME = getenv('DB_NAME') ?: 'pool_my_finger';
    $DB_USER = getenv('DB_USER') ?: 'root';
    $DB_PASS = getenv('DB_PASS') ?: '';

    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";

    // Create PDO connection
    try {
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return $pdo;
    } catch (PDOException $e) {
        fwrite(STDERR, "Connection failed: " . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

db();
