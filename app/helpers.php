<?php
declare(strict_types=1);

use App\Core\Config;

/** Escape chuỗi để in ra HTML. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL tuyệt đối từ đường dẫn nội bộ, ví dụ url('/phim/doi'). */
function url(string $path = '/'): string
{
    $path = '/' . ltrim($path, '/');
    return rtrim((string) Config::get('base_url'), '/') . ($path === '/' ? '/' : $path);
}

/**
 * Mã hóa lại từng đoạn của một đường dẫn đã rawurldecode (vd từ Router::currentPath) trước khi đưa
 * vào header Location: dấu cách/tiếng Việt thành %XX, CR/LF không thể lọt vào header.
 */
function encode_path(string $path): string
{
    return implode('/', array_map('rawurlencode', explode('/', $path)));
}

/** URL cho link menu/cài đặt: link ngoài (http/https) giữ nguyên, đường dẫn nội bộ đi qua url(). */
function link_url(string $href): string
{
    return preg_match('#^https?://#i', $href) ? $href : url($href);
}

/**
 * URL file tĩnh trong public/assets kèm ?v=<hash> lấy từ manifest.json (do tools/build_assets.php sinh ra),
 * để trình duyệt cache lâu dài mà vẫn nhận bản mới khi file đổi.
 */
function asset(string $file): string
{
    static $manifest = null;
    if ($manifest === null) {
        $path = APP_ROOT . '/public/assets/manifest.json';
        $manifest = is_file($path) ? (json_decode((string) file_get_contents($path), true) ?: []) : [];
    }
    $file = ltrim($file, '/');
    $version = $manifest[$file] ?? null;
    return url('/assets/' . $file) . ($version ? '?v=' . $version : '');
}

/**
 * Render một partial/block của theme ngay trong template.
 *
 * @param array<string, mixed> $data
 */
function partial(string $template, array $data = []): string
{
    return \App\Core\View::partial($template, $data);
}

/** Đường dẫn hiện tại có khớp (hoặc nằm dưới) $path không – dùng để đánh dấu menu đang mở. */
function is_current(string $path, string $current): bool
{
    $path = '/' . trim($path, '/');
    return $path === '/' ? $current === '/' : ($current === $path || str_starts_with($current, $path . '/'));
}

/** Lấy giá trị cấu hình theo khóa dạng "db.host". */
function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}
