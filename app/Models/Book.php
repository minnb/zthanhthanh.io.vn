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

        return array_map([self::class, 'decorate'], $books);
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
        return array_map([self::class, 'decorate'], $books);
    }

    /**
     * Thêm post_url (bài viết liên quan). Không tải ảnh bìa: theme hiển thị bìa chữ
     * (partials/book-cover), cover_media_id hiện chưa dùng ở trang công khai.
     *
     * @param array<string, mixed> $book
     * @return array<string, mixed>
     */
    private static function decorate(array $book): array
    {
        if (!empty($book['post_slug']) && !empty($book['category_slug'])) {
            $book['post_url'] = '/' . $book['category_slug'] . '/' . $book['post_slug'];
        } else {
            $book['post_url'] = null;
        }
        return $book;
    }
}
