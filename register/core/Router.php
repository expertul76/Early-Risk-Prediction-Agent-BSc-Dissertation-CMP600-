<?php
class Router {
    private static array $routes = [];

    public static function get(string $pattern, string $controller, string $method): void {
        self::$routes[] = ['GET', $pattern, $controller, $method];
    }

    public static function post(string $pattern, string $controller, string $method): void {
        self::$routes[] = ['POST', $pattern, $controller, $method];
    }

    public static function any(string $pattern, string $controller, string $method): void {
        self::$routes[] = ['GET', $pattern, $controller, $method];
        self::$routes[] = ['POST', $pattern, $controller, $method];
    }

    public static function dispatch(string $uri, string $method): void {
        // Strip query string and trailing slash
        $uri = strtok($uri, '?');
        $uri = rtrim($uri, '/') ?: '/';

        foreach (self::$routes as $route) {
            if ($route[0] !== $method) continue;

            $pattern = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $route[1]) . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $controllerClass = $route[2];
                $action = $route[3];

                require_once __DIR__ . '/../controllers/' . $controllerClass . '.php';
                $controller = new $controllerClass();
                $controller->$action($params);
                return;
            }
        }

        // 404
        http_response_code(404);
        require __DIR__ . '/../views/errors/404.php';
    }
}
