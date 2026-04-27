<?php

use App\Backend\Controllers\TopPoolsController;

$method = $_SERVER['REQUEST_METHOD'] ?? '';

if ($apiPath === 'pools-top') {
    if ($method === 'GET') {
        TopPoolsController::index();
        return;
    }

    methodNotAllowed();
    return;
}

jsonResponse(["error" => "API endpoint not found"], 404);
