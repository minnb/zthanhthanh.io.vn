<?php
declare(strict_types=1);

namespace App\Core;

/** Đọc input của request (POST/GET/JSON) đã chuẩn hóa kiểu. */
final class Request
{
    /** @var array<string, mixed>|null */
    private static ?array $json = null;

    public static function method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    /** Chuỗi đã trim từ POST (hoặc body JSON). Giá trị không phải chuỗi -> $default. */
    public static function str(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? self::json()[$key] ?? null;
        return is_scalar($value) ? trim((string) $value) : $default;
    }

    /** Chuỗi giữ nguyên khoảng trắng (nội dung Markdown). */
    public static function raw(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? self::json()[$key] ?? null;
        // Chuẩn hóa xuống dòng Windows/Mac về \n
        return is_scalar($value) ? str_replace(["\r\n", "\r"], "\n", (string) $value) : $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::str($key);
        return preg_match('/^-?\d{1,9}$/', $value) ? (int) $value : $default;
    }

    /** Checkbox: có gửi và khác "0"/"" là true. */
    public static function bool(string $key): bool
    {
        $value = self::str($key);
        return $value !== '' && $value !== '0';
    }

    /** Tham số GET đã trim. */
    public static function query(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? null;
        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public static function ip(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public static function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return trim((string) ($_SERVER[$key] ?? ''));
    }

    /** Body JSON (cho API admin), giới hạn 2MB. @return array<string, mixed> */
    public static function json(): array
    {
        if (self::$json !== null) {
            return self::$json;
        }
        self::$json = [];
        if (str_starts_with(strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json')) {
            $raw = file_get_contents('php://input', false, null, 0, 2 * 1024 * 1024);
            $data = is_string($raw) ? json_decode($raw, true) : null;
            self::$json = is_array($data) ? $data : [];
        }
        return self::$json;
    }
}
