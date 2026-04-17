<?php

switch ($apiPath) {
    case "auth/login":
        $authController->login();
        break;

    case "auth/validate":
        $authController->validate();
        break;

    case "auth/logout":
        $authController->logout();
        break;

    case "auth/logout-all":
        $authController->logoutAll();
        break;

    case "auth/user":
        $authController->getUserById();
        break;

    default:
        jsonResponse(["error" => "API endpoint not found"], 404);
        break;
}
