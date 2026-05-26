<?php
require_once __DIR__ . '/db.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function getCurrentUser(): ?array {
    startSession();
    if (empty($_SESSION['user_id'])) return null;

    return DB::fetch(
        'SELECT id, email, name, created_at FROM users WHERE id = ?',
        [$_SESSION['user_id']]
    );
}

function requireAuth(): array {
    $user = getCurrentUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    return $user;
}

function jsonResponse(mixed $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function jsonInput(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}
