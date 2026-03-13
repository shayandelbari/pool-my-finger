<?php
/**
 * API Router Layer
 *
 * Hey Ed, this file acts as the API router for our backend.
 * It receives requests from the /api endpoint and determines which controller should handle the request.
 * This file should contain ONLY routing logic, not business logic or SQL.
 * It calls methods in the controllers to process the request.
 *
 * Overall request flow:
 * public/index.php → src/api/index.php (router) → controller → repository → database
 */

// Parse the API path (done in public/index.php)
$apiPath = defined("API_PATH") ? API_PATH : "";

// Placeholder for routing logic
switch ($apiPath) {
    case "":
        // Handle /api root
        // Hey Ed, this is the API root endpoint. You might return API documentation or version info here.
        echo json_encode(["message" => "API root"]);
        break;

    case "pools":
        // Hey Ed, route to PoolController for pool-related endpoints.
        // Example: require_once 'controllers/PoolController.php'; $controller = new PoolController(); $controller->handleRequest();
        echo json_encode(["message" => "Pools endpoint - not implemented yet"]);
        break;

    case "users":
        // Hey Ed, placeholder for user management endpoints. Add UserController here.
        echo json_encode(["message" => "Users endpoint - not implemented yet"]);
        break;

    case "admin":
        // Hey Ed, placeholder for admin endpoints. Add AdminController here.
        echo json_encode(["message" => "Admin endpoint - not implemented yet"]);
        break;

    default:
        // Hey Ed, unknown API endpoint. Return 404.
        http_response_code(404);
        echo json_encode(["error" => "API endpoint not found"]);
        break;
}
