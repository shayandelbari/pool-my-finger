<?php

// Composer autoload for PSR-4 namespaces
require_once __DIR__ . '/../vendor/autoload.php';

// config global variables for easy file access (Frontend, PHP)
require_once __DIR__ . '/../src/config/utils.php';


$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

/*
 |------------------------------------------------------------
 | Normalize base path
 |------------------------------------------------------------
*/

$base = dirname(dirname($_SERVER["SCRIPT_NAME"]));

if ($base !== "/" && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}

$path = rtrim($uri, "/");

if ($path === "") {
    $path = "/";
}

/*
 |------------------------------------------------------------
 | Routes
 |------------------------------------------------------------
*/

$routes = [
    "/" => "home.php",
    "/admin" => "admin.php",
    "/login" => "login.php",
    "/pool" => "pool.php",
];

/*
 |------------------------------------------------------------
 | API
 |------------------------------------------------------------
*/

if (str_starts_with($path, "/api")) {
    $apiPath = substr($path, 4);
    $apiPath = trim($apiPath, "/");
    $apiPath = str_replace("..", "", $apiPath);
    define("API_PATH", $apiPath);
    require __DIR__ . "/../src/backend/api/index.php";
    return;
}

/*
 |------------------------------------------------------------
 | Page routing
 |------------------------------------------------------------
*/

if ($path === "/filter") {
    require __DIR__ . "/../src/frontend/components/filter.php";
    return;
}

if (isset($routes[$path])) {
    require __DIR__ . "/../src/frontend/pages/" . $routes[$path];
    return;
}

/*
 |------------------------------------------------------------
 | 404
 |------------------------------------------------------------
*/

http_response_code(404);
echo "404";