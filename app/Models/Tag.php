<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use App\Core\Slug;

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

    /**
     * Gán đúng danh sách tag cho bài (tạo tag mới nếu chưa có). Tên giữ nguyên như người dùng gõ,
     * slug không dấu. Tên trùng slug (vd "PhimngắnĐỢI" và "phimngandoi") gộp về một tag.
     *
     * @param list<string> $names
     */
    public static function sync(int $postId, array $names): void
    {
        $tagIds = [];
        foreach ($names as $name) {
            $name = trim(ltrim(trim($name), '#'));
            $slug = Slug::make($name, 120);
            if ($name === '' || $slug === '' || isset($tagIds[$slug])) {
                continue;
            }
            $id = Db::value('SELECT id FROM tags WHERE slug = :slug', ['slug' => $slug]);
            if ($id === null) {
                Db::query('INSERT INTO tags (slug, name) VALUES (:slug, :name)', ['slug' => $slug, 'name' => mb_substr($name, 0, 120)]);
                $id = Db::pdo()->lastInsertId();
            }
            $tagIds[$slug] = (int) $id;
        }
        Db::query('DELETE FROM post_tag WHERE post_id = :pid', ['pid' => $postId]);
        foreach ($tagIds as $tagId) {
            Db::query('INSERT INTO post_tag (post_id, tag_id) VALUES (:pid, :tid)', ['pid' => $postId, 'tid' => $tagId]);
        }
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
