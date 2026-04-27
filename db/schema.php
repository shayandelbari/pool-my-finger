<?php
/**
 * schema.php
 *
 * Database bootstrap + schema creation entrypoint.
 * Optional:
 *   --force  Drop app tables before recreation.
 *   --scrape Run scraper and import fresh data snapshot.
 */

$SCRAPER_DIR = __DIR__ . "/../scraper";
$PYTHON_FILE = $SCRAPER_DIR . "/pool-scraper.py";
$OUTPUT_JSON = __DIR__ . "/cache/output.json";

$force = in_array("--force", $argv ?? [], true);
$scrape = in_array("--scrape", $argv ?? [], true);

$dbConfig = get_db_config();
bootstrap_database($dbConfig);
$pdo = connect_database($dbConfig);

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
            'Sunday','Monday','Tuesday',
            'Wednesday','Thursday','Friday','Saturday'
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

if ($scrape) {
    echo "Running montreal-pool-scraper and importing JSON into database..." . PHP_EOL;

    if (!run_scraper_to_json($SCRAPER_DIR, $PYTHON_FILE, $OUTPUT_JSON, $dbConfig)) {
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

function get_db_config()
{
    return [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'pool_my_finger',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
    ];
}

function bootstrap_database(array $dbConfig)
{
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $quotedDbName = str_replace("`", "``", (string) $dbConfig['name']);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$quotedDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    } catch (PDOException $e) {
        fwrite(STDERR, "Database bootstrap failed: " . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

function connect_database(array $dbConfig)
{
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";

    try {
        return new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        fwrite(STDERR, "Connection failed: " . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

function find_in_path(array $candidateNames)
{
    $path = getenv('PATH') ?: '';
    if ($path === '') {
        return null;
    }

    $isWindows = DIRECTORY_SEPARATOR === '\\';
    $pathSeparator = $isWindows ? ';' : ':';
    $paths = array_filter(explode($pathSeparator, $path));

    $extensions = [''];
    if ($isWindows) {
        $pathExt = getenv('PATHEXT') ?: '.EXE;.BAT;.CMD;.COM';
        $extensions = array_filter(explode(';', $pathExt));
        if ($extensions === []) {
            $extensions = ['.EXE', '.BAT', '.CMD', '.COM'];
        }
    }

    foreach ($paths as $dir) {
        foreach ($candidateNames as $name) {
            $nameHasExtension = pathinfo($name, PATHINFO_EXTENSION) !== '';
            if ($isWindows && !$nameHasExtension) {
                foreach ($extensions as $ext) {
                    $candidate = rtrim($dir, "\\/") . DIRECTORY_SEPARATOR . $name . $ext;
                    if (is_file($candidate) && is_executable($candidate)) {
                        return $candidate;
                    }
                }
                continue;
            }

            $candidate = rtrim($dir, "\\/") . DIRECTORY_SEPARATOR . $name;
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }
    }

    return null;
}

function find_python_binary($scraperDir)
{
    $candidates = [
        $scraperDir . DIRECTORY_SEPARATOR . "venv" . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "python",
        $scraperDir . DIRECTORY_SEPARATOR . "venv" . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "python3",
        $scraperDir . DIRECTORY_SEPARATOR . "venv" . DIRECTORY_SEPARATOR . "Scripts" . DIRECTORY_SEPARATOR . "python.exe",
        $scraperDir . DIRECTORY_SEPARATOR . "venv" . DIRECTORY_SEPARATOR . "Scripts" . DIRECTORY_SEPARATOR . "python",
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate) && is_executable($candidate)) {
            return $candidate;
        }
    }

    return find_in_path(['python3', 'python']);
}

function run_scraper_to_json($scraperDir, $pythonFile, $outputJson, array $dbConfig)
{
    $pythonBinary = find_python_binary($scraperDir);
    if ($pythonBinary === null) {
        fwrite(STDERR, "No Python executable found (tried local venv, python3, python)." . PHP_EOL);
        return false;
    }

    $outputDir = dirname($outputJson);
    if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
        fwrite(STDERR, "Unable to create scraper output directory: {$outputDir}" . PHP_EOL);
        return false;
    }

    $command = [
        $pythonBinary,
        $pythonFile,
        "--quiet",
        "--output-json",
        $outputJson,
    ];

    $currentEnv = getenv();
    if (!is_array($currentEnv)) {
        $currentEnv = [];
    }

    $env = array_merge($currentEnv, $_ENV, [
        "DB_HOST" => (string) $dbConfig['host'],
        "DB_PORT" => (string) $dbConfig['port'],
        "DB_NAME" => (string) $dbConfig['name'],
        "DB_USER" => (string) $dbConfig['user'],
        "DB_PASS" => (string) $dbConfig['pass'],
    ]);

    $descriptorSpec = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, $scraperDir, $env);
    if (!is_resource($process)) {
        fwrite(STDERR, "Failed to start scraper process." . PHP_EOL);
        return false;
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);
    if ($exitCode !== 0) {
        fwrite(STDERR, "Scraper execution failed (exit code {$exitCode})." . PHP_EOL);
        if (is_string($stdout) && trim($stdout) !== '') {
            fwrite(STDERR, trim($stdout) . PHP_EOL);
        }
        if (is_string($stderr) && trim($stderr) !== '') {
            fwrite(STDERR, trim($stderr) . PHP_EOL);
        }
        return false;
    }

    return true;
}

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
                        ':day_of_week' => ucfirst($day),
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
