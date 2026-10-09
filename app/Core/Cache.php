<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Cache HTML trang công khai ra file (storage/cache/pages/*.html).
 * - Bật bằng config 'page_cache' => true (mặc định tắt). TTL theo 'page_cache_ttl' (giây, mặc định 600)
 *   để bài hẹn giờ vẫn tự hiện ra mà không cần xóa cache.
 * - Chỉ cache GET/HEAD không có query (hoặc chỉ ?trang=N), không cache /_dev, /admin, không cache khi
 *   trình duyệt có cookie session (người đang đăng nhập admin).
 * - Admin gọi Cache::flush() sau mỗi lần lưu (Bước 5).
 */
final class Cache
{
    public static function enabled(): bool
    {
        return (bool) Config::get('page_cache', false);
    }

    /** Khóa cache cho request hiện tại, hoặc null nếu request này không được cache. */
    public static function keyFor(string $method, string $path, string $query): ?string
    {
        if (!self::enabled() || !in_array($method, ['GET', 'HEAD'], true)) {
            return null;
        }
        if (str_starts_with($path, '/_dev') || str_starts_with($path, '/admin')) {
            return null;
        }
        if ($query !== '' && !preg_match('/^trang=\d{1,4}$/', $query)) {
            return null;
        }
        if (isset($_COOKIE['tt_sess'])) { // tên cookie session đặt trong Core/Session
            return null;
        }
        return sha1($path . '?' . $query);
    }

    public static function get(string $key): ?string
    {
        $file = self::file($key);
        clearstatcache(true, $file); // PHP cache kết quả stat trong tiến trình -> filemtime có thể cũ
        if (!is_file($file)) {
            return null;
        }
        $ttl = max(1, (int) Config::get('page_cache_ttl', 600));
        if (filemtime($file) + $ttl < time()) {
            @unlink($file);
            return null;
        }
        $html = file_get_contents($file);
        return $html === false ? null : $html;
    }

    public static function put(string $key, string $html): void
    {
        $dir = self::dir();
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return; // không ghi được cache thì bỏ qua, trang vẫn chạy bình thường
        }
        // Ghi file tạm rồi rename để không bao giờ phục vụ file ghi dở
        $tmp = $dir . '/' . $key . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, $html, LOCK_EX) !== false) {
            @rename($tmp, self::file($key));
        }
    }

    /** Xóa toàn bộ cache trang. Trả về số file đã xóa. */
    public static function flush(): int
    {
        $count = 0;
        foreach (glob(self::dir() . '/*.html') ?: [] as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }
        return $count;
    }

    private static function dir(): string
    {
        return APP_ROOT . '/storage/cache/pages';
    }

    private static function file(string $key): string
    {
        return self::dir() . '/' . $key . '.html';
    }
}
