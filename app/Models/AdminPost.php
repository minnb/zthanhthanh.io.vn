<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use App\Core\Seo;

/**
 * Truy vấn bài viết phía admin (thấy cả nháp, hẹn giờ, thùng rác).
 * "Hẹn giờ" = status 'published' + published_at ở tương lai (trang công khai tự hiện khi tới giờ).
 */
final class AdminPost
{
    public const STATUSES = ['draft' => 'Nháp', 'pending' => 'Chờ duyệt', 'published' => 'Đã xuất bản'];
    public const SOURCES = ['manual' => 'Tự viết', 'facebook' => 'Facebook', 'ai-draft' => 'AI'];
    private const REVISIONS_KEPT = 10;

    /**
     * @param array{q?: string, status?: string, category?: int, source?: string, trash?: bool} $f
     * @return array{items: list<array<string, mixed>>, total: int, pages: int}
     */
    public static function paginate(array $f, int $page, int $perPage = 20): array
    {
        $where = [!empty($f['trash']) ? 'p.deleted_at IS NOT NULL' : 'p.deleted_at IS NULL'];
        $params = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = 'p.title LIKE :q';
            $params['q'] = '%' . $f['q'] . '%';
        }
        $status = (string) ($f['status'] ?? '');
        if ($status === 'scheduled') {
            $where[] = "p.status = 'published' AND p.published_at > NOW()";
        } elseif ($status === 'published') {
            $where[] = "p.status = 'published' AND p.published_at <= NOW()";
        } elseif (isset(self::STATUSES[$status])) {
            $where[] = 'p.status = :status';
            $params['status'] = $status;
        }
        if (!empty($f['category'])) {
            $where[] = 'p.category_id = :cat';
            $params['cat'] = (int) $f['category'];
        }
        if (isset(self::SOURCES[(string) ($f['source'] ?? '')])) {
            $where[] = 'p.source = :src';
            $params['src'] = (string) $f['source'];
        }
        $sql = implode(' AND ', $where);
        $total = (int) Db::value("SELECT COUNT(*) FROM posts p WHERE {$sql}", $params);
        $perPage = max(1, $perPage);
        $offset = (max(1, $page) - 1) * $perPage;
        $rows = Db::all(
            "SELECT p.id, p.slug, p.title, p.status, p.source, p.published_at, p.updated_at, p.deleted_at,
                    c.name AS category_name, c.slug AS category_slug
             FROM posts p LEFT JOIN categories c ON c.id = p.category_id
             WHERE {$sql} ORDER BY p.updated_at DESC, p.id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return [
            'items' => array_map([self::class, 'withStatus'], $rows),
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /** @return array<string, int> */
    public static function counts(): array
    {
        $row = Db::one(
            "SELECT
               SUM(deleted_at IS NULL AND status = 'draft') AS draft,
               SUM(deleted_at IS NULL AND status = 'pending') AS pending,
               SUM(deleted_at IS NULL AND status = 'published' AND published_at <= NOW()) AS published,
               SUM(deleted_at IS NULL AND status = 'published' AND published_at > NOW()) AS scheduled,
               SUM(deleted_at IS NULL AND status IN ('draft', 'pending') AND source IN ('facebook', 'ai-draft')) AS imported,
               SUM(deleted_at IS NOT NULL) AS trash
             FROM posts"
        ) ?? [];
        return array_map('intval', $row);
    }

    /** @return list<array<string, mixed>> */
    public static function recent(int $limit = 5): array
    {
        $rows = Db::all(
            'SELECT p.id, p.title, p.status, p.source, p.published_at, p.updated_at, p.deleted_at
             FROM posts p WHERE p.deleted_at IS NULL ORDER BY p.updated_at DESC, p.id DESC LIMIT ' . max(1, $limit)
        );
        return array_map([self::class, 'withStatus'], $rows);
    }

    /**
     * Bài đã xuất bản có vấn đề SEO (theo seo.md).
     *
     * @return list<array{id: int, title: string, issues: list<string>}>
     */
    public static function seoWarnings(int $limit = 10): array
    {
        // Lọc sẵn bằng SQL chỉ những bài CÓ vấn đề, rồi LIMIT – không quét mọi bài đã xuất bản
        $rows = Db::all(
            "SELECT p.id, p.title, p.seo_title, p.seo_desc, p.excerpt, p.category_id, p.cover_media_id, m.alt AS cover_alt
             FROM posts p LEFT JOIN media m ON m.id = p.cover_media_id
             WHERE p.deleted_at IS NULL AND p.status = 'published'
               AND (CHAR_LENGTH(COALESCE(NULLIF(p.seo_title, ''), p.title)) > " . Seo::TITLE_MAX . "
                    OR (COALESCE(p.seo_desc, '') = '' AND COALESCE(p.excerpt, '') = '')
                    OR p.category_id IS NULL
                    OR p.cover_media_id IS NULL
                    OR COALESCE(m.alt, '') = '')
             ORDER BY p.updated_at DESC
             LIMIT " . max(1, $limit)
        );
        $out = [];
        foreach ($rows as $row) {
            $issues = [];
            $title = (string) ($row['seo_title'] ?: $row['title']);
            if (mb_strlen($title) > Seo::TITLE_MAX) {
                $issues[] = 'Tiêu đề SEO dài ' . mb_strlen($title) . ' ký tự (> 60)';
            }
            if (trim((string) $row['seo_desc']) === '' && trim((string) $row['excerpt']) === '') {
                $issues[] = 'Thiếu mô tả SEO/tóm tắt';
            }
            if ($row['category_id'] === null) {
                $issues[] = 'Chưa có danh mục (bài không hiện ra ngoài)';
            }
            if ($row['cover_media_id'] !== null && trim((string) $row['cover_alt']) === '') {
                $issues[] = 'Ảnh bìa thiếu alt';
            }
            if ($row['cover_media_id'] === null) {
                $issues[] = 'Chưa có ảnh bìa';
            }
            if ($issues !== []) {
                $out[] = ['id' => (int) $row['id'], 'title' => (string) ($row['title'] ?: '(chưa có tiêu đề)'), 'issues' => $issues];
            }
        }
        return $out;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $row = Db::one(
            'SELECT p.*, c.slug AS category_slug, c.name AS category_name
             FROM posts p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = :id',
            ['id' => $id]
        );
        if ($row === null) {
            return null;
        }
        $row = self::withStatus($row);
        $row['tags'] = implode(', ', array_map(static fn (array $t): string => (string) $t['name'], Tag::forPost($id)));
        $row['cover'] = $row['cover_media_id'] !== null ? Media::find((int) $row['cover_media_id']) : null;
        return $row;
    }

    /** Ghi nguồn bài (tool đăng bài: facebook | ai-draft | manual) + mã tham chiếu chống đăng trùng. */
    public static function setSource(int $id, string $source, ?string $sourceRef): void
    {
        if (!isset(self::SOURCES[$source])) {
            $source = 'manual';
        }
        Db::query('UPDATE posts SET source = :src, source_ref = :ref WHERE id = :id', [
            'src' => $source,
            'ref' => $sourceRef !== null && $sourceRef !== '' ? mb_substr($sourceRef, 0, 255) : null,
            'id' => $id,
        ]);
    }

    /**
     * Bài có cùng mã tham chiếu – tool dùng để không đăng trùng. Gồm cả bài trong thùng rác: source_ref là UNIQUE
     * (schema.sql) nên bài trong thùng rác vẫn "giữ chỗ"; nơi gọi xem deleted_at để báo đúng.
     *
     * @return array<string, mixed>|null
     */
    public static function findBySourceRef(string $sourceRef): ?array
    {
        if ($sourceRef === '') {
            return null;
        }
        $id = Db::value('SELECT id FROM posts WHERE source_ref = :ref LIMIT 1', ['ref' => mb_substr($sourceRef, 0, 255)]);
        return $id === null ? null : self::find((int) $id);
    }

    /** Tạo bản nháp trống để mở trình soạn (có id ngay để tự lưu). */
    public static function createDraft(int $userId): int
    {
        Db::query(
            "INSERT INTO posts (slug, title, body_md, category_id, author_id, status, source)
             VALUES (:slug, '', '', (SELECT id FROM categories ORDER BY sort_order, id LIMIT 1), :uid, 'draft', 'manual')",
            ['slug' => 'nhap-' . date('YmdHis') . '-' . bin2hex(random_bytes(2)), 'uid' => $userId]
        );
        return (int) Db::pdo()->lastInsertId();
    }

    /**
     * Tự lưu tiêu đề + nội dung.
     * - Bài chưa xuất bản (nháp/chờ duyệt): ghi thẳng vào bài.
     * - Bài ĐÃ xuất bản (kể cả hẹn giờ): KHÔNG đụng nội dung đang hiện ngoài web; chỉ giữ một bản nháp
     *   tự lưu trong post_revisions (is_autosave = 1). Web chỉ đổi khi người dùng bấm "Cập nhật".
     *
     * @return 'post'|'draft' Nơi đã lưu
     */
    public static function autosave(int $id, string $status, ?int $userId, string $title, string $body): string
    {
        $title = mb_substr($title, 0, 255);
        if ($status === 'published') {
            self::clearAutosave($id);
            Db::query(
                'INSERT INTO post_revisions (post_id, user_id, title, body_md, is_autosave) VALUES (:pid, :uid, :title, :body, 1)',
                ['pid' => $id, 'uid' => $userId, 'title' => $title, 'body' => $body]
            );
            return 'draft';
        }
        Db::query(
            'UPDATE posts SET title = :title, body_md = :body WHERE id = :id AND deleted_at IS NULL',
            ['title' => $title, 'body' => $body, 'id' => $id]
        );
        return 'post';
    }

    /** Bản nháp tự lưu (của bài đã xuất bản) nếu có. @return array<string, mixed>|null */
    public static function autosaveDraft(int $postId): ?array
    {
        return Db::one(
            'SELECT id, title, body_md, updated_at FROM post_revisions WHERE post_id = :pid AND is_autosave = 1 ORDER BY id DESC LIMIT 1',
            ['pid' => $postId]
        );
    }

    public static function clearAutosave(int $postId): void
    {
        Db::query('DELETE FROM post_revisions WHERE post_id = :pid AND is_autosave = 1', ['pid' => $postId]);
    }

    public static function slugTaken(string $slug, int $exceptId): bool
    {
        return (int) Db::value('SELECT COUNT(*) FROM posts WHERE slug = :slug AND id <> :id', ['slug' => $slug, 'id' => $exceptId]) > 0;
    }

    /**
     * Lưu toàn bộ trường của trình soạn (đã validate ở controller).
     *
     * @param array{title: string, slug: string, excerpt: string, body_md: string, category_id: ?int, status: string,
     *              published_at: ?string, seo_title: string, seo_desc: string, noindex: bool, cover_media_id: ?int} $d
     */
    public static function save(int $id, array $d): void
    {
        Db::query(
            'UPDATE posts SET title = :title, slug = :slug, excerpt = :excerpt, body_md = :body,
                    category_id = :cat, status = :status, published_at = :pub,
                    seo_title = :seo_title, seo_desc = :seo_desc, noindex = :noindex, cover_media_id = :cover
             WHERE id = :id',
            [
                'title' => $d['title'],
                'slug' => $d['slug'],
                'excerpt' => $d['excerpt'] === '' ? null : $d['excerpt'],
                'body' => $d['body_md'],
                'cat' => $d['category_id'],
                'status' => $d['status'],
                'pub' => $d['published_at'],
                'seo_title' => $d['seo_title'] === '' ? null : $d['seo_title'],
                'seo_desc' => $d['seo_desc'] === '' ? null : $d['seo_desc'],
                'noindex' => $d['noindex'] ? 1 : 0,
                'cover' => $d['cover_media_id'],
                'id' => $id,
            ]
        );
    }

    /** Ghi một phiên bản, giữ 10 bản gần nhất (admin.md). */
    public static function addRevision(int $postId, ?int $userId, string $title, string $body): void
    {
        Db::query(
            'INSERT INTO post_revisions (post_id, user_id, title, body_md) VALUES (:pid, :uid, :title, :body)',
            ['pid' => $postId, 'uid' => $userId, 'title' => $title, 'body' => $body]
        );
        // Chỉ tính và dọn các phiên bản thật (không đụng bản nháp tự lưu)
        $keepFrom = Db::value(
            'SELECT id FROM post_revisions WHERE post_id = :pid AND is_autosave = 0 ORDER BY id DESC LIMIT 1 OFFSET ' . (self::REVISIONS_KEPT - 1),
            ['pid' => $postId]
        );
        if ($keepFrom !== null) {
            Db::query(
                'DELETE FROM post_revisions WHERE post_id = :pid AND is_autosave = 0 AND id < :keep',
                ['pid' => $postId, 'keep' => (int) $keepFrom]
            );
        }
    }

    public static function trash(int $id): void
    {
        Db::query('UPDATE posts SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL', ['id' => $id]);
    }

    public static function restore(int $id): void
    {
        Db::query('UPDATE posts SET deleted_at = NULL WHERE id = :id', ['id' => $id]);
    }

    /** Xóa vĩnh viễn bài trong thùng rác quá 30 ngày (admin.md: khôi phục được trong 30 ngày). */
    public static function purgeOldTrash(): int
    {
        return Db::query('DELETE FROM posts WHERE deleted_at IS NOT NULL AND deleted_at < (NOW() - INTERVAL 30 DAY)')->rowCount();
    }

    /**
     * Thêm nhãn trạng thái hiển thị (Hẹn giờ là bài published có ngày đăng tương lai).
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function withStatus(array $row): array
    {
        $status = (string) $row['status'];
        $future = !empty($row['published_at']) && strtotime((string) $row['published_at']) > time();
        $key = $status === 'published' && $future ? 'scheduled' : $status;
        $row['status_key'] = !empty($row['deleted_at']) ? 'trash' : $key;
        $row['status_label'] = match ($row['status_key']) {
            'trash' => 'Thùng rác',
            'scheduled' => 'Hẹn giờ',
            default => self::STATUSES[$key] ?? $key,
        };
        return $row;
    }
}
