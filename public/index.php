<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

/*
 |------------------------------------------------------------
 | Normalize base path
 |------------------------------------------------------------
*/

$base = dirname(dirname($_SERVER['SCRIPT_NAME']));

if ($base !== '/' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}

$path = rtrim($uri, '/');

if ($path === '') {
    $path = '/';
}

/*
 |------------------------------------------------------------
 | Routes
 |------------------------------------------------------------
*/

$routes = [
    '/' => 'home.php',
    '/admin' => 'admin.php',
    '/login' => 'login.php',
    '/pool' => 'pool.php',
];

/*
 |------------------------------------------------------------
 | API
 |------------------------------------------------------------
*/

if (str_starts_with($path, '/api')) {

    $apiPath = substr($path, 4);
    $apiPath = trim($apiPath, '/');
    $apiPath = str_replace('..', '', $apiPath);

    $file = $apiPath === '' ? __DIR__ . '/../src/api/index.php' : __DIR__ . '/../src/api/' . $apiPath . '.php';

    if (file_exists($file)) {
        require $file;
        return;
    }

    http_response_code(404);
    echo "API endpoint not found";
    return;
}

/*
 |------------------------------------------------------------
 | Page routing
 |------------------------------------------------------------
*/

if (isset($routes[$path])) {
    require __DIR__ . '/../src/pages/' . $routes[$path];
    return;
}

/*
 |------------------------------------------------------------
 | 404
 |------------------------------------------------------------
*/

http_response_code(404);
echo "404";