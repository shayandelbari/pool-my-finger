<?php

use App\Backend\Controllers\PoolController;

$method = $_SERVER['REQUEST_METHOD'] ?? '';
$poolId = null;
$poolSchedulesId = null;

if ($apiPath === 'pool-types') {
    if ($method === 'GET') {
        PoolController::indexTypes();
        return;
    }

    methodNotAllowed();
    return;
}

if (preg_match('/^pools\/(\d+)$/', $apiPath, $matches) === 1) {
    $poolId = (int) $matches[1];
}

if (preg_match('/^pools\/(\d+)\/schedules$/', $apiPath, $matches) === 1) {
    $poolSchedulesId = (int) $matches[1];
}

if ($apiPath === 'pools') {
    if ($method === 'GET') {
        PoolController::index();
        return;
    }

    if ($method === 'POST') {
        if (!requireAuthentication()) {
            return;
        }

        PoolController::store();
        return;
    }

    methodNotAllowed();
    return;
}

if ($poolId !== null) {
    if ($method === 'GET') {
        PoolController::show($poolId);
        return;
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        if (!requireAuthentication()) {
            return;
        }

        PoolController::update($poolId);
        return;
    }

    if ($method === 'DELETE') {
        if (!requireAuthentication()) {
            return;
        }

        PoolController::destroy($poolId);
        return;
    }

    methodNotAllowed();
    return;
}

if ($poolSchedulesId !== null) {
    if ($method === 'GET') {
        \App\Backend\Controllers\ScheduleController::showByPoolId($poolSchedulesId);
        return;
    }

    methodNotAllowed();
    return;
}

jsonResponse(["error" => "API endpoint not found"], 404);