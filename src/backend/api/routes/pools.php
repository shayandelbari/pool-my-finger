<?php

$method = $_SERVER['REQUEST_METHOD'] ?? '';
$poolId = null;

if ($apiPath === 'pool-types') {
    if ($method === 'GET') {
        $poolController->indexTypes();
        return;
    }

    methodNotAllowed();
    return;
}

if (preg_match('/^pools\/(\d+)$/', $apiPath, $matches) === 1) {
    $poolId = (int) $matches[1];
}

if ($apiPath === 'pools') {
    if ($method === 'GET') {
        $poolController->index();
        return;
    }

    if ($method === 'POST') {
        if (!requireAuthentication()) {
            return;
        }

        $poolController->store();
        return;
    }

    methodNotAllowed();
    return;
}

if ($poolId !== null) {
    if ($method === 'GET') {
        $poolController->show($poolId);
        return;
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        if (!requireAuthentication()) {
            return;
        }

        $poolController->update($poolId);
        return;
    }

    if ($method === 'DELETE') {
        if (!requireAuthentication()) {
            return;
        }

        $poolController->destroy($poolId);
        return;
    }

    methodNotAllowed();
    return;
}

jsonResponse(["error" => "API endpoint not found"], 404);
