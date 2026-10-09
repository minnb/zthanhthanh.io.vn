<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use App\Core\Markdown;

final class Page
{
    /** @return array<string, mixed>|null */
    public static function findPublished(string $slug, string $lang = 'vi'): ?array
    {
        $row = Db::one(
            "SELECT id, slug, lang, title, body_md, seo_title, seo_desc, translation_of, updated_at
             FROM pages WHERE slug = :slug AND lang = :lang AND status = 'published'",
            ['slug' => $slug, 'lang' => $lang]
        );
        if ($row !== null) {
            $row['html'] = Markdown::toHtml((string) $row['body_md']);
        }
        return $row;
    }

    /** @return list<array{slug: string, lang: string, updated_at: string}> */
    public static function allPublished(): array
    {
        return Db::all("SELECT slug, lang, updated_at FROM pages WHERE status = 'published' ORDER BY lang, slug");
    }

    /**
     * Các bản ngôn ngữ đã xuất bản của cùng một trang (cho hreflang): [lang => path].
     * Nhóm dịch = trang gốc (translation_of NULL) + mọi trang trỏ translation_of về nó.
     * Trả về [] nếu trang không có bản dịch nào.
     *
     * @param array<string, mixed> $page
     * @return array<string, string>
     */
    public static function alternates(array $page): array
    {
        $rootId = (int) ($page['translation_of'] ?: $page['id']);
        $rows = Db::all(
            "SELECT slug, lang FROM pages
             WHERE status = 'published' AND (id = :root OR translation_of = :root2)
             ORDER BY lang",
            ['root' => $rootId, 'root2' => $rootId]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['lang']] = self::path($row);
        }
        return count($map) > 1 ? $map : [];
    }

    /** URL nội bộ của trang: tiếng Việt "/slug", ngôn ngữ khác "/{lang}/slug". @param array<string, mixed> $row */
    public static function path(array $row): string
    {
        return ($row['lang'] ?? 'vi') === 'vi' ? '/' . $row['slug'] : '/' . $row['lang'] . '/' . $row['slug'];
    }
}
