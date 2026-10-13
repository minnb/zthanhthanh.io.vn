<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use App\Core\Image;

/**
 * Ảnh trong thư viện. Cột path = "<YYYY>/<MM>/<slug>-<hash>" (không đuôi), các bản WebP:
 * public/uploads/<path>-<rộng>.webp với rộng thuộc Image::widths(width); ảnh chia sẻ Facebook: <path>-og.jpg.
 * focus_x/focus_y: điểm tâm (0–100 %) – tự dò lúc upload, admin bấm chọn để ghi đè.
 */
final class Media
{
    private const COLUMNS = 'id, path, original_name, mime, width, height, size_bytes, alt, caption, focus_x, focus_y, created_at';

    /** @param array{path: string, width: int, height: int, size: int, mime: string, focus_x?: int, focus_y?: int} $file */
    public static function create(array $file, string $originalName, string $alt, ?int $userId): int
    {
        Db::query(
            'INSERT INTO media (path, original_name, mime, width, height, size_bytes, alt, focus_x, focus_y, uploaded_by)
             VALUES (:path, :orig, :mime, :w, :h, :size, :alt, :fx, :fy, :uid)',
            [
                'path' => $file['path'],
                'orig' => mb_substr($originalName, 0, 255),
                'mime' => $file['mime'],
                'w' => $file['width'],
                'h' => $file['height'],
                'size' => $file['size'],
                'alt' => mb_substr($alt, 0, 255),
                'fx' => self::clampFocus($file['focus_x'] ?? 50),
                'fy' => self::clampFocus($file['focus_y'] ?? 50),
                'uid' => $userId,
            ]
        );
        return (int) Db::pdo()->lastInsertId();
    }

    /** Điểm tâm hợp lệ 0–100 (%). */
    public static function clampFocus(int $value): int
    {
        return min(100, max(0, $value));
    }

    /** Lưu điểm tâm (admin bấm chọn). */
    public static function updateFocus(int $id, int $focusX, int $focusY): void
    {
        Db::query('UPDATE media SET focus_x = :fx, focus_y = :fy WHERE id = :id', [
            'fx' => self::clampFocus($focusX),
            'fy' => self::clampFocus($focusY),
            'id' => $id,
        ]);
    }

    /**
     * Ảnh theo đường dẫn (ảnh chèn trong nội dung Markdown), một truy vấn.
     *
     * @param list<string> $paths
     * @return array<string, array<string, mixed>> theo path
     */
    public static function findByPaths(array $paths): array
    {
        $paths = array_values(array_unique(array_filter($paths, static fn (string $p): bool => $p !== '' && strlen($p) <= 255)));
        if ($paths === []) {
            return [];
        }
        $params = [];
        foreach (array_slice($paths, 0, 100) as $i => $path) {
            $params['p' . $i] = $path;
        }
        $rows = Db::all('SELECT ' . self::COLUMNS . ' FROM media WHERE path IN (:' . implode(', :', array_keys($params)) . ')', $params);
        $byPath = [];
        foreach ($rows as $row) {
            $byPath[(string) $row['path']] = $row;
        }
        return $byPath;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return $id > 0 ? Db::one('SELECT ' . self::COLUMNS . ' FROM media WHERE id = :id', ['id' => $id]) : null;
    }

    /**
     * Nhiều ảnh trong một truy vấn (tránh N+1), kết quả theo id.
     *
     * @param list<int> $ids
     * @return array<int, array<string, mixed>>
     */
    public static function findMany(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }
        // Chỉ số nguyên đã lọc ở trên -> an toàn khi ghép vào IN (...)
        $rows = Db::all('SELECT ' . self::COLUMNS . ' FROM media WHERE id IN (' . implode(',', $ids) . ')');
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }
        return $byId;
    }

    /** @return array{items: list<array<string, mixed>>, total: int} */
    public static function paginate(int $page, int $perPage = 24, string $q = ''): array
    {
        $where = '1 = 1';
        $params = [];
        if ($q !== '') {
            $where = '(alt LIKE :q OR original_name LIKE :q2)';
            $params = ['q' => '%' . $q . '%', 'q2' => '%' . $q . '%'];
        }
        $offset = (max(1, $page) - 1) * $perPage;
        return [
            'items' => Db::all('SELECT ' . self::COLUMNS . " FROM media WHERE {$where} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}", $params),
            'total' => (int) Db::value("SELECT COUNT(*) FROM media WHERE {$where}", $params),
        ];
    }

    public static function updateMeta(int $id, string $alt, string $caption): void
    {
        Db::query('UPDATE media SET alt = :alt, caption = :cap WHERE id = :id', [
            'alt' => mb_substr($alt, 0, 255),
            'cap' => $caption === '' ? null : mb_substr($caption, 0, 255),
            'id' => $id,
        ]);
    }

    /**
     * Nơi đang dùng ảnh (không cho xóa nếu còn dùng – admin.md).
     *
     * @param array<string, mixed> $media
     * @return list<string>
     */
    public static function usage(array $media): array
    {
        $id = (int) $media['id'];
        $uses = [];
        foreach (Db::all('SELECT title FROM posts WHERE cover_media_id = :id', ['id' => $id]) as $row) {
            $uses[] = 'Ảnh bìa bài: ' . ($row['title'] !== '' ? $row['title'] : '(chưa có tiêu đề)');
        }
        foreach (Db::all('SELECT title FROM films WHERE poster_media_id = :id', ['id' => $id]) as $row) {
            $uses[] = 'Poster phim: ' . $row['title'];
        }
        // Khớp MỌI cỡ của ảnh (-480/-960/-1440...), không chỉ cỡ lớn nhất
        $prefix = '%/uploads/' . $media['path'] . '-%';
        if ((int) Db::value("SELECT COUNT(*) FROM settings WHERE `key` = 'default_og_image' AND `value` LIKE :u", ['u' => $prefix]) > 0) {
            $uses[] = 'Ảnh chia sẻ mặc định (Cài đặt)';
        }
        foreach (Db::all('SELECT title FROM posts WHERE body_md LIKE :p AND deleted_at IS NULL', ['p' => $prefix]) as $row) {
            $uses[] = 'Trong nội dung bài: ' . $row['title'];
        }
        return $uses;
    }

    /** Xóa bản ghi và mọi file của ảnh (các bản WebP + ảnh chia sẻ -og.jpg). */
    public static function delete(array $media, string $uploadsDir): void
    {
        $base = rtrim($uploadsDir, '/\\') . '/' . $media['path'];
        $files = array_map(static fn (int $w): string => $base . '-' . $w . '.webp', Image::widths((int) $media['width']));
        $files[] = $base . '-og.jpg';
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        Db::query('DELETE FROM media WHERE id = :id', ['id' => (int) $media['id']]);
    }

    /**
     * Ảnh chia sẻ Facebook (og:image): bản -og.jpg 1200×630 cắt quanh điểm tâm; ảnh tải lên trước khi có tính năng
     * (chưa có file -og.jpg) -> bản lớn nhất như cũ.
     * File -og.jpg bị GHI ĐÈ khi đổi điểm tâm, mà .htaccess cache ảnh 1 năm "immutable" -> gắn ?v=<thời điểm sửa file>
     * để URL đổi theo nội dung (trình duyệt, proxy, Facebook lấy ảnh mới).
     *
     * @param array<string, mixed> $media
     * @return array{url: string, width: int, height: int}
     */
    public static function shareImage(array $media): array
    {
        $relative = '/uploads/' . $media['path'] . '-og.jpg';
        $file = APP_ROOT . '/public' . $relative;
        if (is_file($file)) {
            [$w, $h] = Image::shareSize((int) $media['width'], (int) $media['height']);
            $version = (int) @filemtime($file);
            return ['url' => $relative . ($version > 0 ? '?v=' . $version : ''), 'width' => $w, 'height' => $h];
        }
        return ['url' => self::url($media, 1440), 'width' => (int) $media['width'], 'height' => (int) $media['height']];
    }

    /**
     * object-position để khung `object-fit: cover` tỉ lệ $ratio giữ điểm tâm ở giữa (Image::framePosition).
     *
     * @param array<string, mixed> $media
     */
    public static function framePosition(array $media, float $ratio): string
    {
        return Image::framePosition(
            (int) $media['width'],
            (int) $media['height'],
            ((int) ($media['focus_x'] ?? 50)) / 100,
            ((int) ($media['focus_y'] ?? 50)) / 100,
            $ratio
        );
    }

    /** Đường dẫn nội bộ của một cỡ ảnh (cỡ không tồn tại -> cỡ lớn nhất). @param array<string, mixed> $media */
    public static function url(array $media, int $width): string
    {
        $widths = Image::widths((int) $media['width']);
        $w = in_array($width, $widths, true) ? $width : end($widths);
        return '/uploads/' . $media['path'] . '-' . $w . '.webp';
    }

    /**
     * Dữ liệu cho thẻ <img> responsive (partials/image.php).
     *
     * @param array<string, mixed> $media
     * @param float|null $ratio Tỉ lệ khung hiển thị (Image::FRAME_*) -> trả thêm position (object-position) giữ điểm tâm
     * @return array{src: string, srcset: string, width: int, height: int, alt: string, position: string}
     */
    public static function imageData(array $media, ?float $ratio = null): array
    {
        $parts = [];
        foreach (Image::widths((int) $media['width']) as $w) {
            $parts[] = url(self::url($media, $w)) . ' ' . $w . 'w';
        }
        $widths = Image::widths((int) $media['width']);
        $middle = in_array(960, $widths, true) ? 960 : end($widths);
        return [
            'src' => url(self::url($media, $middle)),
            'srcset' => implode(', ', $parts),
            'width' => (int) $media['width'],
            'height' => (int) $media['height'],
            'alt' => (string) $media['alt'],
            'position' => $ratio !== null && $ratio > 0 ? self::framePosition($media, $ratio) : '',
        ];
    }
}
