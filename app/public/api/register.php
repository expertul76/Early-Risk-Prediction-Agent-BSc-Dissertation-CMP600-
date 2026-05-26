<?php
require_once __DIR__ . '/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = jsonInput();
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

// Validate
if (!$name || !$email || !$password) {
    jsonResponse(['error' => 'Name, email, and password are required'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Invalid email address'], 400);
}

if (strlen($password) < 8) {
    jsonResponse(['error' => 'Password must be at least 8 characters'], 400);
}

// Check if email exists
$existing = DB::fetch('SELECT id FROM users WHERE email = ?', [$email]);
if ($existing) {
    jsonResponse(['error' => 'An account with this email already exists'], 409);
}

// Create user
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
$userId = DB::insert(
    'INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?)',
    [$email, $hash, $name]
);

// Auto-login
startSession();
$_SESSION['user_id'] = (int)$userId;

jsonResponse([
    'user' => ['id' => (int)$userId, 'email' => $email, 'name' => $name],
]);
