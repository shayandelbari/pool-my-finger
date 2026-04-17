<?php

// Core paths
define('SRC_PATH', realpath(__DIR__ . '/..'));
define('ROOT_PATH', realpath(SRC_PATH . '/..'));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('DB_PATH', ROOT_PATH . '/db');
define('SCRAPER_PATH', ROOT_PATH . '/scraper');

// Feature paths (filesystem)
define('COMPONENTS_PATH', SRC_PATH . '/frontend/components');
define('PAGES_PATH', SRC_PATH . '/frontend/pages');
define('ASSETS_PATH', PUBLIC_PATH . '/assets');

// URL paths (for browser)
define('BASE_URL', '/pool-my-finger');
define('API_URL', BASE_URL . '/api');
define('ASSETS_URL', BASE_URL . '/assets');

