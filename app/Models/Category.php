<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

final class Category
{
    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return Db::all('SELECT id, slug, name, description, seo_title, seo_desc FROM categories ORDER BY sort_order, id');
    }

    /** @return array<string, mixed>|null */
    public static function findBySlug(string $slug): ?array
    {
        return Db::one('SELECT id, slug, name, description, seo_title, seo_desc FROM categories WHERE slug = :slug', ['slug' => $slug]);
    }
}
