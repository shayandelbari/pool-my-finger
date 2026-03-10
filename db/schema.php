<?php
/**
 * create_schema.php
 *
 * - Reads DB config from environment variables.
 * - Optional --force flag drops tables before creation.
 */

$force = in_array('--force', $argv, true);

// Read environment variables
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_PORT = getenv('DB_PORT') ?: '3306';
$DB_NAME = getenv('DB_NAME') ?: 'pools_app';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';

$dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";

// Create PDO connection
try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Connection failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

// Drop tables if --force is specified
if ($force) {
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("DROP TABLE IF EXISTS sessions");
        $pdo->exec("DROP TABLE IF EXISTS users");
        $pdo->exec("DROP TABLE IF EXISTS schedules");
        $pdo->exec("DROP TABLE IF EXISTS schedule_types");
        $pdo->exec("DROP TABLE IF EXISTS pools");
        $pdo->exec("DROP TABLE IF EXISTS pool_types");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (PDOException $e) {
        fwrite(STDERR, "Force drop failed: " . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

// Table definitions
$tables = [

    "CREATE TABLE IF NOT EXISTS pools (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        full_address TEXT DEFAULT NULL,
        primary_image_url VARCHAR(2048) DEFAULT NULL,
        website VARCHAR(2048) DEFAULT NULL,
        map_link VARCHAR(2048) DEFAULT NULL,
        phone CHAR(12) DEFAULT NULL,
        pool_type_id SMALLINT UNSIGNED NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_pools_type (pool_type_id),
        CONSTRAINT fk_pools_pool_type
            FOREIGN KEY (pool_type_id)
            REFERENCES pool_types(id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS pool_types (
        id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_pool_types_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS pool_pool_types (
        pool_id BIGINT UNSIGNED NOT NULL,
        pool_type_id SMALLINT UNSIGNED NOT NULL,

        PRIMARY KEY (pool_id, pool_type_id),

        CONSTRAINT fk_pool_pool_types_pool
            FOREIGN KEY (pool_id)
            REFERENCES pools(id)
            ON DELETE CASCADE,

        CONSTRAINT fk_pool_pool_types_type
            FOREIGN KEY (pool_type_id)
            REFERENCES pool_types(id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

    "CREATE TABLE IF NOT EXISTS schedule_types (
        id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_schedule_types_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS schedules (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        pool_id BIGINT UNSIGNED NOT NULL,
        day_of_week ENUM(
            'sunday','monday','tuesday',
            'wednesday','thursday','friday','saturday'
        ) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        schedule_type_id SMALLINT UNSIGNED NOT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_schedules_pool_day (pool_id, day_of_week),
        CONSTRAINT fk_schedules_pool
            FOREIGN KEY (pool_id)
            REFERENCES pools(id)
            ON DELETE CASCADE,
        CONSTRAINT fk_schedules_type
            FOREIGN KEY (schedule_type_id)
            REFERENCES schedule_types(id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS users (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_users_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS sessions (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED NOT NULL,
        token CHAR(128) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        revoked TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY uq_sessions_token (token),
        KEY idx_sessions_user (user_id),
        CONSTRAINT fk_sessions_user
            FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

// Create tables
foreach ($tables as $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        fwrite(STDERR, "Table creation failed: " . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

echo $force
    ? "Schema forcefully recreated.\n"
    : "Schema created.\n";

