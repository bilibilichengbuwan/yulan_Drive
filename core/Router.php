<?php
class Router {
    private static $routes = [];
    
    public static function get($path, $handler, $middleware = []) {
        self::$routes[] = ['GET', $path, $handler, (array)$middleware];
    }
    
    public static function post($path, $handler, $middleware = []) {
        self::$routes[] = ['POST', $path, $handler, (array)$middleware];
    }
    
    public static function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';
        
        foreach (self::$routes as $route) {
            list($routeMethod, $routePath, $handler, $middleware) = $route;
            
            $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';
            
            if ($routeMethod === $method && preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                foreach ($middleware as $mw) {
                    if (is_array($mw)) {
                        $result = call_user_func($mw);
                    } else {
                        $result = $mw();
                    }
                    if ($result === false) {
                        return;
                    }
                }

                return call_user_func_array($handler, [$params]);
            }
        }

        http_response_code(404);
        if (strpos($uri, '/api/') === 0) {
            Helper::error('接口不存在', 404);
        } else {
            include __DIR__ . '/../views/404.php';
        }
    }
}
