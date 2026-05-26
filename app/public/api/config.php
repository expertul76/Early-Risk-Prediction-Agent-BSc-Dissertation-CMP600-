<?php
// Database (20i MySQL)
define('DB_HOST', 'shareddb-b.hosting.stackcp.net');
define('DB_NAME', 'marcel-35336100');
define('DB_USER', 'marcel-35336100');
define('DB_PASS', 'MarcelDB2026!');

// Security
define('BCRYPT_COST', 12);
define('SESSION_LIFETIME', 7 * 24 * 60 * 60); // 7 days

// CORS - allow same origin
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}
