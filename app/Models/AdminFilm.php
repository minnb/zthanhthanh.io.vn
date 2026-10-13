<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

/** Phim + dòng thời gian phía admin. */
final class AdminFilm
{
    public const STATUSES = ['released' => 'Đã hoàn thành', 'in_production' => 'Đang sản xuất', 'in_development' => 'Đang phát triển'];

    /** Trường sửa được trong form (tên cột => kiểu). */
    public const FIELDS = [
        'title' => 'str', 'title_en' => 'str', 'logline' => 'text', 'synopsis_md' => 'text', 'genre' => 'str',
        'duration_min' => 'int', 'duration_note' => 'str', 'year' => 'int', 'poster_media_id' => 'int',
        'trailer_url' => 'str', 'credits_md' => 'text', 'actors' => 'str', 'awards_md' => 'text',
        'badge' => 'str', 'related_tag' => 'str', 'status' => 'str', 'is_featured' => 'bool',
        'seo_title' => 'str', 'seo_desc' => 'str',
    ];

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return Db::all('SELECT id, slug, title, status, is_featured, year, updated_at FROM films ORDER BY sort_order, id');
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $film = Db::one('SELECT * FROM films WHERE id = :id', ['id' => $id]);
        if ($film !== null) {
            $film['poster'] = $film['poster_media_id'] !== null ? Media::find((int) $film['poster_media_id']) : null;
        }
        return $film;
    }

    /**
     * @param array<string, mixed> $data Đã validate, khóa thuộc FIELDS
     */
    public static function update(int $id, array $data): void
    {
        $sets = [];
        $params = ['id' => $id];
        foreach (self::FIELDS as $column => $type) {
            if (!array_key_exists($column, $data)) {
                continue;
            }
            $sets[] = "{$column} = :{$column}";
            $value = $data[$column];
            $params[$column] = $type === 'bool' ? ($value ? 1 : 0) : ($value === '' ? null : $value);
        }
        if ($sets === []) {
            return;
        }
        Db::query('UPDATE films SET ' . implode(', ', $sets) . ' WHERE id = :id', $params);
        // Chỉ một phim nổi bật trên trang chủ
        if (!empty($data['is_featured'])) {
            Db::query('UPDATE films SET is_featured = 0 WHERE id <> :id', ['id' => $id]);
        }
    }

    // ------------------------------------------------------------------
    // Dòng thời gian
    // ------------------------------------------------------------------

    /** @return list<array<string, mixed>> */
    public static function milestones(int $filmId): array
    {
        return Db::all(
            'SELECT id, film_id, label, title, description, is_highlight, sort_order FROM film_milestones
             WHERE film_id = :f ORDER BY sort_order, id',
            ['f' => $filmId]
        );
    }

    /** @return array<string, mixed>|null */
    public static function milestone(int $id): ?array
    {
        return Db::one('SELECT * FROM film_milestones WHERE id = :id', ['id' => $id]);
    }

    public static function addMilestone(int $filmId, string $label, string $title, string $description, bool $highlight): void
    {
        $next = (int) Db::value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM film_milestones WHERE film_id = :f', ['f' => $filmId]);
        Db::query(
            'INSERT INTO film_milestones (film_id, label, title, description, is_highlight, sort_order)
             VALUES (:f, :label, :title, :desc, :hl, :sort)',
            ['f' => $filmId, 'label' => $label, 'title' => $title, 'desc' => $description === '' ? null : $description, 'hl' => $highlight ? 1 : 0, 'sort' => $next]
        );
    }

    public static function updateMilestone(int $id, string $label, string $title, string $description, bool $highlight): void
    {
        Db::query(
            'UPDATE film_milestones SET label = :label, title = :title, description = :desc, is_highlight = :hl WHERE id = :id',
            ['label' => $label, 'title' => $title, 'desc' => $description === '' ? null : $description, 'hl' => $highlight ? 1 : 0, 'id' => $id]
        );
    }

    public static function deleteMilestone(int $id): void
    {
        Db::query('DELETE FROM film_milestones WHERE id = :id', ['id' => $id]);
    }

    /** Đổi chỗ với mốc liền trước/sau (nút lên/xuống – admin.md: không dùng kéo thả). */
    public static function moveMilestone(int $id, string $direction): void
    {
        $current = self::milestone($id);
        if ($current === null) {
            return;
        }
        // Chuẩn hóa sort_order 1..n trước để việc hoán đổi luôn đúng
        $list = self::milestones((int) $current['film_id']);
        foreach ($list as $i => $row) {
            if ((int) $row['sort_order'] !== $i + 1) {
                Db::query('UPDATE film_milestones SET sort_order = :s WHERE id = :id', ['s' => $i + 1, 'id' => $row['id']]);
            }
        }
        $index = array_search($id, array_map(static fn (array $r): int => (int) $r['id'], $list), true);
        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        if ($index === false || !isset($list[$swap])) {
            return;
        }
        Db::query('UPDATE film_milestones SET sort_order = :s WHERE id = :id', ['s' => $swap + 1, 'id' => $id]);
        Db::query('UPDATE film_milestones SET sort_order = :s WHERE id = :id', ['s' => $index + 1, 'id' => $list[$swap]['id']]);
    }
}
