<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Router tối giản: GET/POST, tham số dạng {slug} (chữ thường, số, gạch nối).
 * Handler là [ControllerClass::class, 'method'], method nhận các tham số route theo tên.
 */
final class Router
{
    /** @var array<string, list<array{regex: string, handler: array{0: class-string, 1: string}}>> */
    private array $routes = [];

    /** @param array{0: class-string, 1: string} $handler */
    public function get(string $pattern, array $handler): self
    {
        return $this->add('GET', $pattern, $handler);
    }

    /** @param array{0: class-string, 1: string} $handler */
    public function post(string $pattern, array $handler): self
    {
        return $this->add('POST', $pattern, $handler);
    }

    /** @param array{0: class-string, 1: string} $handler */
    public function add(string $method, string $pattern, array $handler): self
    {
        // Đoạn chữ cố định được preg_quote (vd "." trong /sitemap.xml), chỉ {tham_so} thành nhóm bắt
        $parts = preg_split('#(\{[a-z_]+\})#', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$pattern];
        $regex = '';
        foreach ($parts as $part) {
            $regex .= preg_match('#^\{([a-z_]+)\}$#', $part, $m)
                ? '(?P<' . $m[1] . '>[a-z0-9-]+)'
                : preg_quote($part, '#');
        }
        $this->routes[$method][] = ['regex' => '#^' . $regex . '$#', 'handler' => $handler];
        return $this;
    }

    /**
     * Trả về nội dung trang. Ném HttpException nếu không khớp route nào.
     */
    public function dispatch(string $method, string $path): string
    {
        // HEAD dùng chung route với GET
        $lookup = $method === 'HEAD' ? 'GET' : $method;

        foreach ($this->routes[$lookup] ?? [] as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            [$class, $action] = $route['handler'];
            return (string) (new $class())->$action(...$params);
        }

        // Có route ở method khác -> 405
        foreach ($this->routes as $other => $routes) {
            if ($other === $lookup) {
                continue;
            }
            foreach ($routes as $route) {
                if (preg_match($route['regex'], $path)) {
                    throw new HttpException(405, 'Method Not Allowed', ['Allow' => $other]);
                }
            }
        }

        throw HttpException::notFound();
    }

    /** Lấy path của request, đã bỏ base path và query string. Luôn bắt đầu bằng "/". */
    public static function currentPath(): string
    {
        $uri = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri = rawurldecode($uri);
        $base = Config::basePath();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        if (str_ends_with($uri, '/index.php')) {
            $uri = substr($uri, 0, -strlen('index.php'));
        }
        return '/' . ltrim($uri, '/');
    }
}
