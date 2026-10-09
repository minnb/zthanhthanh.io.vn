<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

final class Tag
{
    /** @return array<string, mixed>|null */
    public static function findBySlug(string $slug): ?array
    {
        return Db::one('SELECT id, slug, name FROM tags WHERE slug = :slug', ['slug' => $slug]);
    }

    /**
     * Tag đủ nội dung để index (≥ $min bài công khai) – khớp quy tắc noindex trang tag mỏng.
     *
     * @return list<array{slug: string, posts: int}>
     */
    public static function indexable(int $min = 3): array
    {
        return Db::all(
            "SELECT t.slug, COUNT(*) AS posts FROM tags t
             JOIN post_tag pt ON pt.tag_id = t.id
             JOIN posts p ON p.id = pt.post_id
             WHERE p.status = 'published' AND p.deleted_at IS NULL AND p.published_at <= NOW()
               AND p.category_id IS NOT NULL
             GROUP BY t.id, t.slug HAVING COUNT(*) >= :min ORDER BY t.slug",
            ['min' => $min]
        );
    }

    /** @return list<array<string, mixed>> */
    public static function forPost(int $postId): array
    {
        return Db::all(
            'SELECT t.slug, t.name FROM tags t JOIN post_tag pt ON pt.tag_id = t.id
             WHERE pt.post_id = :id ORDER BY t.name',
            ['id' => $postId]
        );
    }
}
