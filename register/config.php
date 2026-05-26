<?php
// Application
define('APP_NAME', getenv('APP_NAME') ?: 'Student Risk Prediction');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('APP_BASE', getenv('APP_BASE') ?: '/register');

// Database
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: '');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Gemini AI
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('GEMINI_MODEL', getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash');

// Email
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@example.com');

// Security
define('BCRYPT_COST', 12);
