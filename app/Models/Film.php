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
        $films = Db::all('SELECT * FROM films ORDER BY sort_order, id');
        // Poster của mọi phim trong một truy vấn (tránh N+1)
        $posters = Media::findMany(array_map(static fn (array $f): int => (int) ($f['poster_media_id'] ?? 0), $films));
        return array_map(
            static fn (array $f): array => self::decorate($f, $posters[(int) ($f['poster_media_id'] ?? 0)] ?? null),
            $films
        );
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

    /** Thời điểm sửa phim/dòng thời gian gần nhất (lastmod cho trang chủ trong sitemap). */
    public static function lastModified(): ?string
    {
        // MAX bỏ qua NULL; tránh GREATEST(datetime, 0) vì trộn kiểu DATETIME với số
        $value = Db::value(
            'SELECT MAX(t) FROM (
                SELECT MAX(updated_at) AS t FROM films
                UNION ALL
                SELECT MAX(updated_at) FROM film_milestones
            ) AS x'
        );
        return $value === null ? null : (string) $value;
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
     * @param array<string, mixed>|null|false $poster Poster đã tải sẵn (all()); false = tự tải
     * @return array<string, mixed>
     */
    private static function decorate(array $film, array|null|false $poster = false): array
    {
        $kind = trim(explode('·', (string) ($film['genre'] ?? ''))[0]);
        $film['url'] = '/phim/' . $film['slug'];
        if ($poster === false) {
            $poster = !empty($film['poster_media_id']) ? Media::find((int) $film['poster_media_id']) : null;
        }
        $film['poster'] = $poster;
        $film['caption'] = trim($kind . (!empty($film['year']) ? ' · ' . $film['year'] : ''), ' ·');
        $film['status_label'] = match ($film['status']) {
            'in_development' => 'Đang phát triển',
            'in_production' => 'Đang sản xuất',
            default => 'Đã hoàn thành',
        };
        return $film;
    }
}
