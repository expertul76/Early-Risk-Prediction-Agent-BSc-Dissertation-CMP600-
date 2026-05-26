<?php
require_once __DIR__ . '/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = jsonInput();
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$email || !$password) {
    jsonResponse(['error' => 'Email and password are required'], 400);
}

$user = DB::fetch('SELECT id, email, name, password_hash FROM users WHERE email = ?', [$email]);

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(['error' => 'Invalid email or password'], 401);
}

startSession();
$_SESSION['user_id'] = (int)$user['id'];

jsonResponse([
    'user' => [
        'id' => (int)$user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
    ],
]);
