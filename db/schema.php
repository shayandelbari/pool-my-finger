<?php
/**
 * schema.php
 *
 * Uses existing db() connection.
 * Optional --force flag drops tables before creation.
 */

require_once __DIR__ . "/connection.php";

$SCRAPER_DIR = __DIR__ . "/../scraper";
$PYTHON_FILE = $SCRAPER_DIR . "/pool-scraper.py";
$OUTPUT_JSON = __DIR__ . "/cache/output.json";
$pdo = db();

$force = in_array("--force", $argv ?? [], true);
$scrape = in_array("--scrape", $argv ?? [], true);

// Drop tables if --force is specified
if ($force) {
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("DROP TABLE IF EXISTS time_blocks");
        $pdo->exec("DROP TABLE IF EXISTS sessions");
        $pdo->exec("DROP TABLE IF EXISTS users");
        $pdo->exec("DROP TABLE IF EXISTS schedules");
        $pdo->exec("DROP TABLE IF EXISTS schedule_types");
        $pdo->exec("DROP TABLE IF EXISTS pool_pool_types");
        $pdo->exec("DROP TABLE IF EXISTS pools");
        $pdo->exec("DROP TABLE IF EXISTS pool_types");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (PDOException $e) {
        fwrite(STDERR, "Force drop failed: " . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

$tables = [
    "CREATE TABLE IF NOT EXISTS pool_types (
        id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_pool_types_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS pools (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        full_address TEXT DEFAULT NULL,
        primary_image_url VARCHAR(2048) DEFAULT NULL,
        website VARCHAR(2048) DEFAULT NULL,
        map_link VARCHAR(2048) DEFAULT NULL,
        latt DOUBLE DEFAULT NULL,
        longt DOUBLE DEFAULT NULL,
        phone CHAR(12) DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

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
        schedule_type_id SMALLINT UNSIGNED NOT NULL,
        effective_date DATE NOT NULL,
        end_date DATE NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_schedules_pool (pool_id),
        KEY idx_schedules_dates (effective_date, end_date),
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

    "CREATE TABLE IF NOT EXISTS time_blocks (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        schedule_id BIGINT UNSIGNED NOT NULL,
        day_of_week ENUM(
            'sunday','monday','tuesday',
            'wednesday','thursday','friday','saturday'
        ) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        label VARCHAR(100) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY idx_blocks_schedule_day (schedule_id, day_of_week),
        CONSTRAINT fk_blocks_schedule
            FOREIGN KEY (schedule_id)
            REFERENCES schedules(id)
            ON DELETE CASCADE
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

foreach ($tables as $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        fwrite(STDERR, "Table creation failed: " . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

echo $force ? "Schema forcefully recreated.\n" : "Schema created.\n";

function find_python_binary($scraperDir)
{
    $venvPython = $scraperDir . "/venv/bin/python";
    if (is_file($venvPython) && is_executable($venvPython)) {
        return $venvPython;
    }

    $python3 = trim((string) shell_exec("command -v python3 2>/dev/null"));
    if ($python3 !== "") {
        return $python3;
    }

    $python = trim((string) shell_exec("command -v python 2>/dev/null"));
    if ($python !== "") {
        return $python;
    }

    return null;
}

function run_scraper_to_json($scraperDir, $pythonFile, $outputJson)
{
    $pythonBinary = find_python_binary($scraperDir);
    if ($pythonBinary === null) {
        fwrite(STDERR, "No Python executable found (tried venv, python3, python)." . PHP_EOL);
        return false;
    }

    $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
    $dbPort = getenv('DB_PORT') ?: '3306';
    $dbName = getenv('DB_NAME') ?: 'pool_my_finger';
    $dbUser = getenv('DB_USER') ?: 'root';
    $dbPass = getenv('DB_PASS') ?: '';

    $envPrefix = "DB_HOST=" . escapeshellarg($dbHost)
        . " DB_PORT=" . escapeshellarg($dbPort)
        . " DB_NAME=" . escapeshellarg($dbName)
        . " DB_USER=" . escapeshellarg($dbUser)
        . " DB_PASS=" . escapeshellarg($dbPass)
        . " ENV=" . escapeshellarg('prod');

    $cmd = "cd " . escapeshellarg($scraperDir)
        . " && " . $envPrefix
        . " " . escapeshellarg($pythonBinary)
        . " " . escapeshellarg($pythonFile)
        . " --quiet --output-json " . escapeshellarg($outputJson)
        . " 2>&1";

    $outputLines = [];
    $exitCode = 0;
    exec($cmd, $outputLines, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, "Scraper execution failed (exit code {$exitCode})." . PHP_EOL);
        if (!empty($outputLines)) {
            fwrite(STDERR, implode(PHP_EOL, $outputLines) . PHP_EOL);
        }
        return false;
    }

    return true;
}

// TODO: add inserting latt and longt from the scraper output
function import_scraped_json(PDO $pdo, $outputJson)
{
    if (!is_file($outputJson)) {
        throw new RuntimeException("Scraper output not found at {$outputJson}");
    }

    $raw = file_get_contents($outputJson);
    if ($raw === false || trim($raw) === "") {
        throw new RuntimeException("Scraper output JSON is empty.");
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload) || !isset($payload['pools']) || !is_array($payload['pools'])) {
        throw new RuntimeException("Invalid scraper JSON format.");
    }

    $pools = $payload['pools'];

    $pdo->beginTransaction();
    try {
        // Keep imported data idempotent between runs.
        $pdo->exec("DELETE FROM time_blocks");
        $pdo->exec("DELETE FROM schedules");
        $pdo->exec("DELETE FROM pool_pool_types");
        $pdo->exec("DELETE FROM pools");
        $pdo->exec("DELETE FROM schedule_types");
        $pdo->exec("DELETE FROM pool_types");

        $insertPoolType = $pdo->prepare(
            "INSERT INTO pool_types (name, description)
             VALUES (:name, :description)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), description = VALUES(description)"
        );

        $insertPool = $pdo->prepare(
            "INSERT INTO pools (name, full_address, primary_image_url, website, map_link, latt, longt, phone, is_active)
             VALUES (:name, :full_address, :primary_image_url, :website, :map_link, :latt, :longt, :phone, :is_active)"
        );

        $insertPoolPoolType = $pdo->prepare(
            "INSERT IGNORE INTO pool_pool_types (pool_id, pool_type_id)
             VALUES (:pool_id, :pool_type_id)"
        );

        $insertScheduleType = $pdo->prepare(
            "INSERT INTO schedule_types (name, description)
             VALUES (:name, :description)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), description = VALUES(description)"
        );

        $insertSchedule = $pdo->prepare(
            "INSERT INTO schedules (pool_id, schedule_type_id, effective_date, end_date)
             VALUES (:pool_id, :schedule_type_id, :effective_date, :end_date)"
        );

        $insertTimeBlock = $pdo->prepare(
            "INSERT INTO time_blocks (schedule_id, day_of_week, start_time, end_time, label)
             VALUES (:schedule_id, :day_of_week, :start_time, :end_time, :label)"
        );

        $importedPools = 0;

        foreach ($pools as $pool) {
            if (!is_array($pool)) {
                continue;
            }

            $record = (isset($pool['db_record']) && is_array($pool['db_record'])) ? $pool['db_record'] : $pool;

            $poolTypeNames = [];
            if (isset($record['pool_type_names']) && is_array($record['pool_type_names'])) {
                foreach ($record['pool_type_names'] as $name) {
                    if (!is_string($name)) {
                        continue;
                    }

                    $normalized = mb_substr(trim($name), 0, 50);
                    if ($normalized !== '') {
                        $poolTypeNames[] = $normalized;
                    }
                }
            }

            if ($poolTypeNames === []) {
                $fallbackTypeName = trim((string) ($record['pool_type_name'] ?? $pool['pool_type_name'] ?? $pool['pool_type'] ?? 'Unknown'));
                if ($fallbackTypeName === '') {
                    $fallbackTypeName = 'Unknown';
                }
                $poolTypeNames[] = mb_substr($fallbackTypeName, 0, 50);
            }

            $poolTypeNames = array_values(array_unique($poolTypeNames));

            $poolTypeIds = [];
            foreach ($poolTypeNames as $poolTypeName) {
                $insertPoolType->execute([
                    ':name' => $poolTypeName,
                    ':description' => $record['pool_type_description'] ?? (string) ($pool['pool_type'] ?? null),
                ]);
                $poolTypeIds[] = (int) $pdo->lastInsertId();
            }

            $insertPool->execute([
                ':name' => mb_substr((string) ($record['name'] ?? $pool['name'] ?? 'Unknown Pool'), 0, 255),
                ':full_address' => $record['full_address'] ?? $pool['address'] ?? null,
                ':primary_image_url' => $record['primary_image_url'] ?? $pool['primary_image_url'] ?? null,
                ':website' => $record['website'] ?? $pool['url'] ?? null,
                ':map_link' => $record['map_link'] ?? $pool['map_link'] ?? null,
                ':latt' => isset($record['latt']) ? (float) $record['latt'] : (isset($pool['latitude']) ? (float) $pool['latitude'] : null),
                ':longt' => isset($record['longt']) ? (float) $record['longt'] : (isset($pool['longitude']) ? (float) $pool['longitude'] : null),
                ':phone' => $record['phone'] ?? $pool['phone'] ?? null,
                ':is_active' => isset($record['is_active']) ? (int) $record['is_active'] : (!empty($pool['is_active']) ? 1 : 0),
            ]);
            $poolId = (int) $pdo->lastInsertId();

            foreach ($poolTypeIds as $poolTypeId) {
                $insertPoolPoolType->execute([
                    ':pool_id' => $poolId,
                    ':pool_type_id' => $poolTypeId,
                ]);
            }

            $schedules = $record['schedules'] ?? $pool['schedules'] ?? [];
            if (!is_array($schedules)) {
                $schedules = [];
            }

            foreach ($schedules as $schedule) {
                if (!is_array($schedule)) {
                    continue;
                }

                $scheduleTypeName = trim((string) ($schedule['activity_name'] ?? $schedule['activity'] ?? 'General'));
                if ($scheduleTypeName === '') {
                    $scheduleTypeName = 'General';
                }
                $scheduleTypeName = mb_substr($scheduleTypeName, 0, 50);

                $insertScheduleType->execute([
                    ':name' => $scheduleTypeName,
                    ':description' => null,
                ]);
                $scheduleTypeId = (int) $pdo->lastInsertId();

                $effectiveDate = (string) ($schedule['effective_date_iso'] ?? date('Y-m-d'));
                $endDate = (string) ($schedule['end_date_iso'] ?? $effectiveDate);

                $insertSchedule->execute([
                    ':pool_id' => $poolId,
                    ':schedule_type_id' => $scheduleTypeId,
                    ':effective_date' => $effectiveDate,
                    ':end_date' => $endDate,
                ]);
                $scheduleId = (int) $pdo->lastInsertId();

                $timeBlocks = $schedule['time_blocks'] ?? [];
                if (!is_array($timeBlocks)) {
                    $timeBlocks = [];
                }

                foreach ($timeBlocks as $block) {
                    if (!is_array($block)) {
                        continue;
                    }

                    $day = strtolower((string) ($block['day_of_week'] ?? $block['day'] ?? ''));
                    if (!in_array($day, ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'], true)) {
                        continue;
                    }

                    $startTime = (string) ($block['start_time'] ?? $block['start'] ?? '00:00');
                    $endTime = (string) ($block['end_time'] ?? $block['end'] ?? $startTime);

                    $insertTimeBlock->execute([
                        ':schedule_id' => $scheduleId,
                        ':day_of_week' => $day,
                        ':start_time' => $startTime,
                        ':end_time' => $endTime,
                        ':label' => isset($block['label']) ? mb_substr((string) $block['label'], 0, 100) : null,
                    ]);
                }
            }

            $importedPools++;
        }

        $pdo->commit();
        echo "Imported {$importedPools} pools from scraper output." . PHP_EOL;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

if ($scrape) {
    echo "Running montreal-pool-scraper and importing JSON into database..." . PHP_EOL;

    if (!run_scraper_to_json($SCRAPER_DIR, $PYTHON_FILE, $OUTPUT_JSON)) {
        exit(1);
    }

    try {
        import_scraped_json($pdo, $OUTPUT_JSON);
        echo "Scrape and import completed successfully." . PHP_EOL;
    } catch (Throwable $e) {
        fwrite(STDERR, "Import failed: " . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}