<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Session an toàn, chỉ mở khi cần (admin, form). Trang công khai không mở session
 * để cache HTML và CDN hoạt động tốt.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || str_starts_with((string) Config::get('base_url'), 'https://');

        session_name('tt_sess');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => (Config::basePath() ?: '') . '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
    }

    /** Gọi sau khi đăng nhập / đổi quyền để chống session fixation. */
    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }
}
