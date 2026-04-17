<?php

$method = $_SERVER['REQUEST_METHOD'] ?? '';
$singleScheduleMatch = preg_match('/^schedules\/(\d+)$/', $apiPath, $matches) === 1;

if ($apiPath === 'schedules' || $singleScheduleMatch) {
    if ($method !== 'GET') {
        methodNotAllowed();
        return;
    }

    jsonResponse([
        "message" => "Schedules endpoint - not implemented yet",
        "path" => $apiPath,
    ]);
    return;
}

jsonResponse(["error" => "API endpoint not found"], 404);
