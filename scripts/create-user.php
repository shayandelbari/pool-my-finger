<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/backend/bootstrap.php';

use App\Backend\Services\UserService;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line." . PHP_EOL);
    exit(1);
}

$args = $argv ?? [];
$scriptName = array_shift($args);

if (count($args) < 2 || in_array('--help', $args, true) || in_array('-h', $args, true)) {
    fwrite(STDOUT, "Usage: php scripts/create-user.php <username> <password>" . PHP_EOL);
    exit(count($args) < 2 ? 1 : 0);
}

[$username, $password] = $args;

try {
    $userId = UserService::createUser($username, $password);
    fwrite(STDOUT, "Created user {$username} with id {$userId}." . PHP_EOL);
    exit(0);
} catch (InvalidArgumentException | RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, "Unexpected error: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
