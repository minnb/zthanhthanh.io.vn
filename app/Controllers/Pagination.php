<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;

/** Đọc tham số ?trang=N cho các trang danh sách. */
final class Pagination
{
    /**
     * Trang 1 dùng URL sạch (không ?trang=1): ?trang=1 -> 301. Giá trị sai -> 404.
     */
    public static function currentPage(string $path): int
    {
        if (!isset($_GET['trang'])) {
            return 1;
        }
        $raw = (string) $_GET['trang'];
        if (!ctype_digit($raw) || (int) $raw < 1) {
            throw HttpException::notFound();
        }
        if ((int) $raw === 1) {
            throw new HttpException(301, '', ['Location' => url($path)]);
        }
        return (int) $raw;
    }

    /** Đường dẫn chuẩn của một trang danh sách: trang 1 không có ?trang (dùng cho canonical). */
    public static function path(string $basePath, int $page): string
    {
        return $page > 1 ? $basePath . '?trang=' . $page : $basePath;
    }

    /** Ném 404 nếu số trang vượt quá tổng số trang (trang > 1). */
    public static function assertInRange(int $page, int $pages): void
    {
        if ($page > 1 && $page > $pages) {
            throw HttpException::notFound();
        }
    }
}
