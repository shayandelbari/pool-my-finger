<?php
/**
 * Backend Bootstrap
 * 
 * Initializes the backend environment:
 * - Loads configuration and path constants
 * - Initializes database connection
 * 
 * This file should be required once at the entry point of all backend operations
 * (currently src/backend/api/index.php)
 */

require_once dirname(__DIR__) . '/config/utils.php';
require_once DB_PATH . '/connection.php';
