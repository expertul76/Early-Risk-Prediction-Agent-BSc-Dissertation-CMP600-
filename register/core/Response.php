<?php
class Response {
    public static function view(string $view, array $data = []): void {
        extract($data);
        $user = Auth::user();
        require __DIR__ . '/../views/' . $view . '.php';
    }

    public static function json($data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }

    public static function back(): void {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
        header('Location: ' . $referer);
        exit;
    }

    public static function flash(string $type, string $message): void {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public static function getFlash(): ?array {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
