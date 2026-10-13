<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

/** Trang tĩnh phía admin. */
final class AdminPage
{
    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return Db::all('SELECT id, slug, lang, title, status, translation_of, updated_at FROM pages ORDER BY lang, slug');
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Db::one('SELECT * FROM pages WHERE id = :id', ['id' => $id]);
    }

    /**
     * @param array{title: string, body_md: string, status: string, seo_title: string, seo_desc: string, translation_of: ?int} $d
     */
    public static function update(int $id, array $d): void
    {
        Db::query(
            'UPDATE pages SET title = :title, body_md = :body, status = :status,
                    seo_title = :st, seo_desc = :sd, translation_of = :tr
             WHERE id = :id',
            [
                'title' => $d['title'],
                'body' => $d['body_md'],
                'status' => $d['status'],
                'st' => $d['seo_title'] === '' ? null : $d['seo_title'],
                'sd' => $d['seo_desc'] === '' ? null : $d['seo_desc'],
                'tr' => $d['translation_of'],
                'id' => $id,
            ]
        );
    }
}
