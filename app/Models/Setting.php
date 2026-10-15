<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

/** Cài đặt dạng key/value trong bảng settings, nạp một lần mỗi request. */
final class Setting
{
    /** @var array<string, string>|null */
    private static ?array $cache = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        self::$cache ??= self::loadAll();
        return self::$cache[$key] ?? $default;
    }

    /** Giá trị dạng JSON (menu, thứ tự khối trang chủ...). */
    public static function json(string $key, mixed $default = null): mixed
    {
        $raw = self::get($key);
        if ($raw === null) {
            return $default;
        }
        $value = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE ? $value : $default;
    }

    /** Lưu (thêm hoặc cập nhật) một cài đặt. */
    public static function set(string $key, string $value): void
    {
        Db::query(
            'INSERT INTO settings (`key`, `value`) VALUES (:k, :v) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            ['k' => $key, 'v' => $value]
        );
        self::$cache = null;
    }

    /**
     * Thời điểm sửa cài đặt gần nhất (lastmod cho trang chủ trong sitemap).
     * Bỏ qua khóa nội bộ stats_* (salt thống kê đổi mỗi ngày, không phải nội dung).
     */
    public static function lastModified(): ?string
    {
        $value = Db::value("SELECT MAX(updated_at) FROM settings WHERE `key` NOT LIKE 'stats\\_%'");
        return $value === null ? null : (string) $value;
    }

    /** @return array<string, string> */
    private static function loadAll(): array
    {
        $items = [];
        foreach (Db::all('SELECT `key`, `value` FROM settings') as $row) {
            $items[(string) $row['key']] = (string) $row['value'];
        }
        return $items;
    }
}
