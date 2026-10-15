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
 * Nội dung một file CSS đã build trong public/assets (sinh từ theme bởi tools/build_assets.php), để nhúng
 * vào <style> thay cho thêm một <link> chặn render. Chỉ nhận tên "abc-xyz.css"; không có file -> ''.
 */
function asset_inline_css(string $file): string
{
    if (!preg_match('/^[a-z0-9-]+\.css$/', $file)) {
        return '';
    }
    $path = APP_ROOT . '/public/assets/' . $file;
    $css = is_file($path) ? (string) file_get_contents($path) : '';
    // CSS do theme tự sinh; vẫn chặn chuỗi đóng thẻ để nội dung không thể thoát khỏi <style>
    return str_ireplace('</style', '<\/style', $css);
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

/**
 * Icon SVG lấy từ sprite partials/icons.php (in một lần trong layout). Tên không hợp lệ -> chuỗi rỗng.
 * Icon chỉ để trang trí (aria-hidden): nút/link chứa icon phải tự có nhãn chữ hoặc aria-label.
 */
function icon(string $name, string $class = 'i--sm'): string
{
    if (!preg_match('/^[a-z0-9-]+$/', $name)) {
        return '';
    }
    return '<svg class="i ' . e($class) . '" aria-hidden="true" focusable="false"><use href="#i-' . $name . '"/></svg>';
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
