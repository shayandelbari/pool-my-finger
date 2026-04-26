<?php
use App\Backend\Services\Exceptions\SessionValidationException;
use App\Backend\Services\SessionService;

require_once dirname(__DIR__) . '/bootstrap.php';

$apiPath = defined("API_PATH") ? API_PATH : "";

/**
 * @param array<string, mixed> $payload
 */
function jsonResponse(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
}

function methodNotAllowed(): void
{
    jsonResponse(["error" => "Method not allowed."], 405);
}

function requireAuthentication(): bool
{
    $token = $_COOKIE['pool_my_finger_session'] ?? null;
    if (!is_string($token) || trim($token) === '') {
        jsonResponse(["error" => "Missing session cookie."], 401);
        return false;
    }

    try {
        SessionService::validateSessionToken(trim($token));
        return true;
    } catch (SessionValidationException $e) {
        jsonResponse(["error" => "Session invalid."], 401);
        return false;
    }
}

switch ($apiPath) {
    case "":
        jsonResponse(["message" => "API root"]);
        break;

    default:
        if (str_starts_with($apiPath, 'auth/')) {
            require __DIR__ . '/routes/auth.php';
            break;
        }

        if (str_starts_with($apiPath, 'pool') || str_starts_with($apiPath, 'pools')) {
            require __DIR__ . '/routes/pools.php';
            break;
        }

        if (str_starts_with($apiPath, 'schedule') || str_starts_with($apiPath, 'schedules')) {
            require __DIR__ . '/routes/schedules.php';
            break;
        }

        jsonResponse(["error" => "API endpoint not found"], 404);
        break;
}
