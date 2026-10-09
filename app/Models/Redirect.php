<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

/** Chuyển hướng 301 (slug cũ -> mới) lưu trong bảng redirects. */
final class Redirect
{
    /** Số bước tối đa khi đi theo chuỗi redirect A -> B -> C. */
    private const MAX_HOPS = 5;

    /**
     * Tìm đích cuối cùng cho một đường dẫn nội bộ và ghi nhận lượt truy cập ở bản ghi đầu.
     * - Chỉ chấp nhận đích nội bộ (bắt đầu bằng "/", không phải "//") để tránh open redirect.
     * - Đi theo chuỗi redirect (A -> B, B -> C => trả C) để trình duyệt chỉ chuyển hướng 1 lần.
     * - Phát hiện vòng lặp (A -> B -> A) hoặc chuỗi quá dài -> trả null (trang sẽ 404 thay vì lặp vô hạn).
     *
     * @return array{path: string, status: int}|null
     */
    public static function resolve(string $path): ?array
    {
        if (!self::isInternal($path)) {
            return null;
        }
        $first = null;
        $visited = [$path => true];
        $current = $path;

        for ($hop = 0; $hop < self::MAX_HOPS; $hop++) {
            $row = Db::one(
                'SELECT id, new_path, status_code FROM redirects WHERE old_path = :p',
                ['p' => self::pathOnly($current)]
            );
            if ($row === null) {
                break; // $current là đích cuối
            }
            $target = (string) $row['new_path'];
            if (!self::isInternal($target) || isset($visited[$target])) {
                return null; // đích ngoài hoặc vòng lặp
            }
            $first ??= $row;
            $visited[$target] = true;
            $current = $target;
        }

        if ($first === null || $current === $path) {
            return null;
        }
        if ($hop === self::MAX_HOPS && Db::one('SELECT id FROM redirects WHERE old_path = :p', ['p' => self::pathOnly($current)]) !== null) {
            return null; // chuỗi dài hơn giới hạn
        }

        Db::query('UPDATE redirects SET hits = hits + 1, last_hit_at = NOW() WHERE id = :id', ['id' => $first['id']]);
        $status = in_array((int) $first['status_code'], [301, 302, 308], true) ? (int) $first['status_code'] : 301;
        return ['path' => $current, 'status' => $status];
    }

    private static function isInternal(string $path): bool
    {
        return $path !== '' && $path[0] === '/' && !str_starts_with($path, '//');
    }

    /** old_path lưu không kèm query; đích có thể có "?…" nên bỏ phần query khi tra tiếp. */
    private static function pathOnly(string $path): string
    {
        return explode('?', $path, 2)[0];
    }
}
