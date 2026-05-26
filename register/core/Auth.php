<?php
class Auth {
    private static ?array $currentUser = null;

    public static function init(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(int $userId): void {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));

        Database::insert('auth_sessions', [
            'id' => $token,
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'expires_at' => $expires,
        ]);

        $_SESSION['auth_token'] = $token;
        self::$currentUser = null;
    }

    public static function logout(): void {
        if (isset($_SESSION['auth_token'])) {
            Database::delete('auth_sessions', 'id = ?', [$_SESSION['auth_token']]);
        }
        session_destroy();
        self::$currentUser = null;
    }

    public static function user(): ?array {
        if (self::$currentUser !== null) return self::$currentUser;

        if (!isset($_SESSION['auth_token'])) return null;

        $session = Database::fetch(
            'SELECT s.user_id, s.expires_at FROM auth_sessions s WHERE s.id = ?',
            [$_SESSION['auth_token']]
        );

        if (!$session || strtotime($session['expires_at']) < time()) {
            if ($session) {
                Database::delete('auth_sessions', 'id = ?', [$_SESSION['auth_token']]);
            }
            unset($_SESSION['auth_token']);
            return null;
        }

        self::$currentUser = Database::fetch(
            'SELECT u.*, s.name as school_name, s.slug as school_slug
             FROM users u JOIN schools s ON u.school_id = s.id
             WHERE u.id = ? AND u.is_active = 1',
            [$session['user_id']]
        );

        return self::$currentUser;
    }

    public static function check(): bool {
        return self::user() !== null;
    }

    public static function requireAuth(): array {
        $user = self::user();
        if (!$user) {
            header('Location: /register/login');
            exit;
        }
        return $user;
    }

    public static function requireAdmin(): array {
        $user = self::requireAuth();
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo 'Access denied. Admin only.';
            exit;
        }
        return $user;
    }

    public static function schoolId(): int {
        return (int)(self::user()['school_id'] ?? 0);
    }

    // CSRF
    public static function csrfToken(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::csrfToken()) . '">';
    }

    public static function verifyCsrf(): bool {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return hash_equals(self::csrfToken(), $token);
    }

    public static function requireCsrf(): void {
        if (!self::verifyCsrf()) {
            http_response_code(403);
            echo 'Invalid CSRF token. Please refresh and try again.';
            exit;
        }
    }
}
