<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use App\Core\Markdown;

final class Film
{
    /** Phim nổi bật trên trang chủ. @return array<string, mixed>|null */
    public static function featured(): ?array
    {
        $film = Db::one('SELECT * FROM films WHERE is_featured = 1 ORDER BY sort_order, id LIMIT 1');
        return $film === null ? null : self::decorate($film);
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return array_map([self::class, 'decorate'], Db::all('SELECT * FROM films ORDER BY sort_order, id'));
    }

    /** Phim kèm nội dung đã render. @return array<string, mixed>|null */
    public static function findBySlug(string $slug): ?array
    {
        $film = Db::one('SELECT * FROM films WHERE slug = :slug', ['slug' => $slug]);
        if ($film === null) {
            return null;
        }
        $film = self::decorate($film);
        foreach (['synopsis_md' => 'synopsis_html', 'credits_md' => 'credits_html', 'awards_md' => 'awards_html'] as $md => $html) {
            $film[$html] = !empty($film[$md]) ? Markdown::toHtml((string) $film[$md]) : '';
        }
        return $film;
    }

    /** @return list<array<string, mixed>> */
    public static function milestones(int $filmId): array
    {
        return Db::all(
            'SELECT label, title, description, is_highlight FROM film_milestones
             WHERE film_id = :film ORDER BY sort_order, id',
            ['film' => $filmId]
        );
    }

    /**
     * Thêm các trường hiển thị: url, caption poster ("Phim ngắn · 2026"), nhãn trạng thái.
     *
     * @param array<string, mixed> $film
     * @return array<string, mixed>
     */
    private static function decorate(array $film): array
    {
        $kind = trim(explode('·', (string) ($film['genre'] ?? ''))[0]);
        $film['url'] = '/phim/' . $film['slug'];
        $film['caption'] = trim($kind . (!empty($film['year']) ? ' · ' . $film['year'] : ''), ' ·');
        $film['status_label'] = match ($film['status']) {
            'in_development' => 'Đang phát triển',
            'in_production' => 'Đang sản xuất',
            default => 'Đã hoàn thành',
        };
        return $film;
    }
}
