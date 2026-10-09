<?php
declare(strict_types=1);

use App\Core\Cache;
use App\Core\CanonicalHost;
use App\Core\ErrorHandler;
use App\Core\HttpException;
use App\Core\Router;
use App\Core\View;
use App\Models\Redirect;
use App\Models\Site;

/*
 * Front controller. Mọi request (trừ file tĩnh có thật) đều đi qua đây.
 * Nếu hosting không cho đặt document root vào public/, sửa APP_ROOT thành
 * thư mục chứa app/, themes/, storage/ (nằm ngoài public_html).
 */
define('APP_ROOT', dirname(__DIR__));

try {
    require APP_ROOT . '/app/bootstrap.php';
} catch (RuntimeException $e) {
    // Lỗi cấu hình trước khi ErrorHandler được đăng ký (vd thiếu config.php)
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit($e->getMessage());
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = Router::currentPath();
$query = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);

// Production: ép HTTPS + host chuẩn theo base_url. GET/HEAD -> 301; method khác -> 308 (giữ method + body)
$canonicalUrl = CanonicalHost::redirectUrl(rtrim($path, '/') ?: '/', $query);
if ($canonicalUrl !== null) {
    header('Location: ' . $canonicalUrl, true, in_array($method, ['GET', 'HEAD'], true) ? 301 : 308);
    exit;
}

// URL chuẩn: không có "/" ở cuối (trừ trang chủ) -> 301
if ($path !== '/' && str_ends_with($path, '/')) {
    header('Location: ' . url(encode_path(rtrim($path, '/'))) . ($query !== '' ? '?' . $query : ''), true, 301);
    exit;
}

// Cache HTML (nếu bật): phục vụ ngay, không chạm DB
$cacheKey = Cache::keyFor($method, $path, $query);
if ($cacheKey !== null && ($cached = Cache::get($cacheKey)) !== null) {
    header('Content-Type: text/html; charset=utf-8');
    header('X-Cache: HIT');
    if ($method !== 'HEAD') {
        echo $cached;
    }
    exit;
}

// Dữ liệu dùng chung cho layout (header, footer, menu)
try {
    $site = Site::info();
} catch (Throwable $e) {
    ErrorHandler::log($e);
    $site = Site::fallback();
}
View::share(['site' => $site, 'currentPath' => $path]);

$router = new Router();
(require APP_ROOT . '/app/routes.php')($router);

$status = 200;
try {
    $body = $router->dispatch($method, $path);
} catch (HttpException $e) {
    $status = $e->status;
    // 404: thử bảng redirects (slug cũ -> mới) trước khi báo không tìm thấy
    if ($status === 404 && ($redirect = Redirect::resolve($path)) !== null) {
        // Đích lấy từ DB (đã là URL hợp lệ, có thể kèm "?"): nối query cũ bằng "&" nếu đích đã có query
        $target = $redirect['path'];
        if ($query !== '') {
            $target .= (str_contains($target, '?') ? '&' : '?') . $query;
        }
        header('Location: ' . url($target), true, $redirect['status']);
        exit;
    }
    http_response_code($status);
    foreach ($e->headers as $name => $value) {
        header($name . ': ' . $value);
    }
    // 3xx (chuyển hướng) chỉ cần header, không render trang
    $body = '';
    if ($status >= 400) {
        header('X-Robots-Tag: noindex');
        $body = View::render('pages/error', [
            'title' => $status === 404 ? 'Không tìm thấy trang' : 'Lỗi',
            'noindex' => true,
            'status' => $status,
            'message' => $e->getMessage(),
        ]);
    }
}

// Controller trả XML/text (sitemap, robots, feed) tự đặt Content-Type; mặc định là HTML
$hasContentType = (bool) preg_grep('/^Content-Type:/i', headers_list());
if (!$hasContentType) {
    header('Content-Type: text/html; charset=utf-8');
    if ($status === 200 && $cacheKey !== null) {
        Cache::put($cacheKey, $body);
        header('X-Cache: MISS');
    }
}

if ($method !== 'HEAD') {
    echo $body;
}
