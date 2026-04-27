<?php

use App\Backend\Controllers\AuthController;

switch ($apiPath) {
    case "auth/login":
        AuthController::login();
        break;

    case "auth/validate":
        AuthController::validate();
        break;

    case "auth/logout":
        AuthController::logout();
        break;

    case "auth/logout-all":
        AuthController::logoutAll();
        break;

    case "auth/user":
        AuthController::getUserById();
        break;

    default:
        jsonResponse(["error" => "API endpoint not found"], 404);
        break;
}
