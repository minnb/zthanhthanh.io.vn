<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use App\Core\Markdown;

/**
 * Bài viết. Mọi truy vấn công khai chỉ lấy bài đã xuất bản, chưa xóa, đã tới giờ đăng.
 */
final class Post
{
    // Bài không có danh mục không có URL hợp lệ (/{danh-muc}/{slug}) -> không công khai
    private const PUBLIC_WHERE = "p.status = 'published' AND p.deleted_at IS NULL AND p.published_at <= NOW()
        AND p.category_id IS NOT NULL";

    private const SELECT = 'SELECT p.id, p.slug, p.title, p.excerpt, p.body_md, p.published_at, p.updated_at,
            p.seo_title, p.seo_desc, p.noindex, p.cover_media_id,
            c.slug AS category_slug, c.name AS category_name
        FROM posts p
        LEFT JOIN categories c ON c.id = p.category_id';

    /** @return list<array<string, mixed>> */
    public static function latest(int $limit = 3): array
    {
        $rows = Db::all(self::SELECT . ' WHERE ' . self::PUBLIC_WHERE . ' ORDER BY p.published_at DESC, p.id DESC LIMIT ' . max(1, $limit));
        return array_map([self::class, 'card'], $rows);
    }

    /**
     * Danh sách có phân trang theo danh mục hoặc tag.
     *
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, per_page: int}
     */
    public static function paginate(?int $categoryId, ?int $tagId, int $page, int $perPage = 9): array
    {
        $perPage = max(1, $perPage);
        $where = self::PUBLIC_WHERE;
        $params = [];
        if ($categoryId !== null) {
            $where .= ' AND p.category_id = :cat';
            $params['cat'] = $categoryId;
        }
        if ($tagId !== null) {
            $where .= ' AND EXISTS (SELECT 1 FROM post_tag pt WHERE pt.post_id = p.id AND pt.tag_id = :tag)';
            $params['tag'] = $tagId;
        }

        $total = (int) Db::value('SELECT COUNT(*) FROM posts p WHERE ' . $where, $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $rows = Db::all(
            self::SELECT . ' WHERE ' . $where . " ORDER BY p.published_at DESC, p.id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        return [
            'items' => array_map([self::class, 'card'], $rows),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    /** @return array<string, mixed>|null */
    public static function findPublished(string $categorySlug, string $slug): ?array
    {
        $row = Db::one(
            self::SELECT . ' WHERE ' . self::PUBLIC_WHERE . ' AND p.slug = :slug AND c.slug = :cat',
            ['slug' => $slug, 'cat' => $categorySlug]
        );
        if ($row === null) {
            return null;
        }
        $row['url'] = self::url($row);
        $row['html'] = Markdown::toHtml((string) $row['body_md']);
        $row['text'] = Markdown::htmlToText($row['html']);
        $row['reading_minutes'] = Markdown::readingMinutesFromText($row['text']);
        $row['tags'] = Tag::forPost((int) $row['id']);
        return $row;
    }

    /**
     * Bài liên quan: ưu tiên chung tag, sau đó cùng danh mục.
     *
     * @param array<string, mixed> $post
     * @return list<array<string, mixed>>
     */
    public static function related(array $post, int $limit = 3): array
    {
        $rows = Db::all(
            self::SELECT . ' WHERE ' . self::PUBLIC_WHERE . ' AND p.id <> :id
             ORDER BY (SELECT COUNT(*) FROM post_tag a JOIN post_tag b ON a.tag_id = b.tag_id
                       WHERE a.post_id = p.id AND b.post_id = :id2) DESC,
                      (c.slug = :cat) DESC, p.published_at DESC
             LIMIT ' . max(1, $limit),
            ['id' => $post['id'], 'id2' => $post['id'], 'cat' => $post['category_slug'] ?? '']
        );
        return array_map([self::class, 'card'], $rows);
    }

    /**
     * Bài theo tag (dùng cho trang phim: bài hậu trường liên quan).
     *
     * @return list<array<string, mixed>>
     */
    public static function byTagSlug(string $tagSlug, int $limit = 3): array
    {
        $rows = Db::all(
            self::SELECT . ' JOIN post_tag pt ON pt.post_id = p.id JOIN tags t ON t.id = pt.tag_id
             WHERE ' . self::PUBLIC_WHERE . ' AND t.slug = :tag
             ORDER BY p.published_at DESC LIMIT ' . max(1, $limit),
            ['tag' => $tagSlug]
        );
        return array_map([self::class, 'card'], $rows);
    }

    /**
     * Bài cho sitemap: công khai và không noindex.
     *
     * @return list<array{url: string, lastmod: string}>
     */
    public static function forSitemap(): array
    {
        $rows = Db::all(
            'SELECT p.slug, p.published_at, p.updated_at, c.slug AS category_slug
             FROM posts p LEFT JOIN categories c ON c.id = p.category_id
             WHERE ' . self::PUBLIC_WHERE . ' AND p.noindex = 0
             ORDER BY p.published_at DESC'
        );
        return array_map(static fn (array $r): array => [
            'url' => self::url($r),
            'lastmod' => (string) ($r['updated_at'] ?: $r['published_at']),
        ], $rows);
    }

    /**
     * Bài cho RSS: mới nhất trước, kèm tóm tắt.
     *
     * @return list<array<string, mixed>>
     */
    public static function forFeed(int $limit = 20): array
    {
        $rows = Db::all(self::SELECT . ' WHERE ' . self::PUBLIC_WHERE . ' AND p.noindex = 0
            ORDER BY p.published_at DESC, p.id DESC LIMIT ' . max(1, $limit));
        return array_map([self::class, 'card'], $rows);
    }

    /** Thời điểm bài công khai được sửa gần nhất (lastmod cho trang chủ/danh mục). */
    public static function lastModified(?int $categoryId = null): ?string
    {
        $sql = 'SELECT MAX(GREATEST(p.published_at, COALESCE(p.updated_at, p.published_at))) FROM posts p WHERE ' . self::PUBLIC_WHERE;
        $params = [];
        if ($categoryId !== null) {
            $sql .= ' AND p.category_id = :cat';
            $params['cat'] = $categoryId;
        }
        $value = Db::value($sql, $params);
        return $value === null ? null : (string) $value;
    }

    /** @param array<string, mixed> $row */
    public static function url(array $row): string
    {
        return '/' . ($row['category_slug'] ?? 'bai-viet') . '/' . $row['slug'];
    }

    /**
     * Dữ liệu gọn cho thẻ bài viết.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function card(array $row): array
    {
        // Render Markdown một lần, dùng chung cho excerpt và số phút đọc
        $text = Markdown::toText((string) $row['body_md']);
        $excerpt = trim((string) ($row['excerpt'] ?? ''));
        return [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'url' => self::url($row),
            'excerpt' => $excerpt !== '' ? $excerpt : Markdown::excerpt($text, 150),
            'category' => (string) ($row['category_name'] ?? ''),
            'category_slug' => (string) ($row['category_slug'] ?? ''),
            'published_at' => (string) $row['published_at'],
            'reading_minutes' => Markdown::readingMinutesFromText($text),
        ];
    }
}
