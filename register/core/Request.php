<?php
class Request {
    public static function method(): string {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function isPost(): bool {
        return self::method() === 'POST';
    }

    public static function get(string $key, $default = null) {
        return $_GET[$key] ?? $default;
    }

    public static function post(string $key, $default = null) {
        return $_POST[$key] ?? $default;
    }

    public static function input(string $key, $default = null) {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public static function all(): array {
        return array_merge($_GET, $_POST);
    }

    public static function json(): array {
        $body = file_get_contents('php://input');
        return json_decode($body, true) ?: [];
    }

    public static function file(string $key): ?array {
        return $_FILES[$key] ?? null;
    }

    public static function uri(): string {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        // Strip query string
        $uri = strtok($uri, '?');
        return $uri ?: '/';
    }
}
