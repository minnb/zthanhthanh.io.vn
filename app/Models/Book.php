<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

final class Book
{
    /** @return list<array<string, mixed>> */
    public static function allVisible(): array
    {
        $books = Db::all('SELECT b.*, p.slug as post_slug, p.category_id as post_category_id, c.slug as category_slug
            FROM books b
            LEFT JOIN posts p ON b.post_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE b.is_visible = 1
            ORDER BY b.sort_order ASC, b.id ASC');

        $covers = Media::findMany(array_map(static fn (array $b): int => (int) ($b['cover_media_id'] ?? 0), $books));
        return array_map(
            static fn (array $b): array => self::decorate($b, $covers[(int) ($b['cover_media_id'] ?? 0)] ?? null),
            $books
        );
    }

    /** @return list<array<string, mixed>> */
    public static function forPost(int $postId): array
    {
        $books = Db::all('SELECT b.*, p.slug as post_slug, p.category_id as post_category_id, c.slug as category_slug
            FROM books b
            LEFT JOIN posts p ON b.post_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE b.post_id = :post_id AND b.is_visible = 1
            ORDER BY b.sort_order ASC, b.id ASC', [
            'post_id' => $postId,
        ]);
        if ($books === []) {
            return [];
        }
        $covers = Media::findMany(array_map(static fn (array $b): int => (int) ($b['cover_media_id'] ?? 0), $books));
        return array_map(
            static fn (array $b): array => self::decorate($b, $covers[(int) ($b['cover_media_id'] ?? 0)] ?? null),
            $books
        );
    }

    /**
     * @param array<string, mixed> $book
     * @param array<string, mixed>|null $cover
     * @return array<string, mixed>
     */
    private static function decorate(array $book, ?array $cover = null): array
    {
        $book['cover'] = $cover ?? ($book['cover_media_id'] ? Media::find((int) $book['cover_media_id']) : null);
        if (!empty($book['post_slug']) && !empty($book['category_slug'])) {
            $book['post_url'] = '/' . $book['category_slug'] . '/' . $book['post_slug'];
        } else {
            $book['post_url'] = null;
        }
        return $book;
    }
}
