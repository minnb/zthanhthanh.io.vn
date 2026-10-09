<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    /** @param array<string, mixed> $items */
    public static function load(array $items): void
    {
        self::$items = $items;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public static function isLocal(): bool
    {
        return self::get('env') === 'local';
    }

    /** Phần path của base_url, ví dụ "/projects/thanhthanh/src/thanhthanh/public" ở local, "" ở production. */
    public static function basePath(): string
    {
        $path = (string) parse_url((string) self::get('base_url', ''), PHP_URL_PATH);
        return rtrim($path, '/');
    }
}
