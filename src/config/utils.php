<?php

// Core paths
define('SRC_PATH', realpath(__DIR__ . '/..'));
define('ROOT_PATH', realpath(SRC_PATH . '/..'));
define('PUBLIC_PATH', ROOT_PATH . '/public');

// Feature paths (filesystem)
define('COMPONENTS_PATH', SRC_PATH . '/frontend/components');
define('PAGES_PATH', SRC_PATH . '/frontend/pages');
define('ASSETS_PATH', PUBLIC_PATH . '/assets');

// URL paths (for browser)
define('BASE_URL', '/pool-my-finger');
define('ASSETS_URL', BASE_URL . '/assets');

/* This is a pain the ass RN, not using
//Function to call a page by just using PHP and the page's name as parameter
function urlByPageName($page) {
    return BASE_URL . '/index.php?page='. urlencode($page);
}
    */