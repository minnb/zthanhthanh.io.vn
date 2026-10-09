<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Sinh toàn bộ thẻ SEO trong <head> theo .claude/rules/seo.md:
 * title ≤ 60, description ≤ 155, canonical tuyệt đối, robots, hreflang, Open Graph, Twitter card,
 * RSS, xác minh Search Console và JSON-LD (BreadcrumbList tự sinh từ breadcrumb của trang).
 */
final class Seo
{
    public const TITLE_MAX = 60;
    public const DESCRIPTION_MAX = 155;
    public const OG_IMAGE_WIDTH = 1200;
    public const OG_IMAGE_HEIGHT = 630;

    /**
     * "Tiêu đề – Tên site". Quá dài thì bỏ hậu tố tên site, vẫn dài thì cắt theo từ.
     * Trang chủ (không có tiêu đề riêng): "Tên site – tagline".
     */
    public static function title(?string $pageTitle, string $siteName, string $tagline = ''): string
    {
        $pageTitle = trim((string) $pageTitle);
        if ($pageTitle === '' || $pageTitle === $siteName) {
            $full = $tagline !== '' ? $siteName . ' – ' . $tagline : $siteName;
            return self::limit($full, self::TITLE_MAX);
        }
        $full = $pageTitle . ' – ' . $siteName;
        return mb_strlen($full) <= self::TITLE_MAX ? $full : self::limit($pageTitle, self::TITLE_MAX);
    }

    public static function description(?string $text): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', (string) $text));
        return $text === '' ? '' : self::limit($text, self::DESCRIPTION_MAX);
    }

    /**
     * HTML các thẻ SEO cho <head> (trừ <title>).
     *
     * @param array<string, mixed> $v Biến của trang: site, currentPath, description, noindex, lang,
     *        canonicalPath, ogType, image, alternates [lang => path], breadcrumb, jsonLd (list), publishedAt, modifiedAt
     */
    public static function headTags(array $v, string $pageTitle): string
    {
        $site = is_array($v['site'] ?? null) ? $v['site'] : [];
        $lang = (string) ($v['lang'] ?? 'vi');
        $description = self::description($v['description'] ?? null);
        $noindex = !empty($v['noindex']);
        $canonical = url((string) ($v['canonicalPath'] ?? $v['currentPath'] ?? '/'));
        $image = self::absolute((string) ($v['image'] ?? '')) ?: self::defaultImage($site);

        $tags = [];
        if ($description !== '') {
            $tags[] = self::meta('name', 'description', $description);
        }
        if ($noindex) {
            $tags[] = self::meta('name', 'robots', 'noindex,follow');
        } else {
            // Trang noindex không khai báo canonical/hreflang để tránh tín hiệu mâu thuẫn
            $tags[] = '<link rel="canonical" href="' . e($canonical) . '">';
            $alternates = is_array($v['alternates'] ?? null) ? $v['alternates'] : [];
            if (count($alternates) > 1) {
                foreach ($alternates as $altLang => $path) {
                    $tags[] = '<link rel="alternate" hreflang="' . e((string) $altLang) . '" href="' . e(url((string) $path)) . '">';
                }
                $default = $alternates['vi'] ?? reset($alternates);
                $tags[] = '<link rel="alternate" hreflang="x-default" href="' . e(url((string) $default)) . '">';
            }
        }

        $verification = (string) Config::get('search_console_verification', '');
        if ($verification !== '' && ($v['currentPath'] ?? '') === '/') {
            $tags[] = self::meta('name', 'google-site-verification', $verification);
        }

        // Open Graph + Twitter
        $ogTitle = self::limit(trim((string) ($v['title'] ?? '')) ?: (string) ($site['name'] ?? ''), 95);
        $tags[] = self::meta('property', 'og:type', (string) ($v['ogType'] ?? 'website'));
        $tags[] = self::meta('property', 'og:site_name', (string) ($site['name'] ?? ''));
        $tags[] = self::meta('property', 'og:locale', $lang === 'en' ? 'en_US' : 'vi_VN');
        $tags[] = self::meta('property', 'og:title', $ogTitle !== '' ? $ogTitle : $pageTitle);
        if ($description !== '') {
            $tags[] = self::meta('property', 'og:description', $description);
        }
        $tags[] = self::meta('property', 'og:url', $canonical);
        if ($image !== '') {
            $tags[] = self::meta('property', 'og:image', $image);
            if (!isset($v['image']) || $v['image'] === '') {
                $tags[] = self::meta('property', 'og:image:width', (string) self::OG_IMAGE_WIDTH);
                $tags[] = self::meta('property', 'og:image:height', (string) self::OG_IMAGE_HEIGHT);
            }
        }
        if (!empty($v['publishedAt'])) {
            $tags[] = self::meta('property', 'article:published_time', self::isoDate((string) $v['publishedAt']));
        }
        if (!empty($v['modifiedAt'])) {
            $tags[] = self::meta('property', 'article:modified_time', self::isoDate((string) $v['modifiedAt']));
        }
        $tags[] = self::meta('name', 'twitter:card', 'summary_large_image');

        $tags[] = '<link rel="alternate" type="application/rss+xml" title="' . e((string) ($site['name'] ?? '')) . '" href="' . e(url('/feed.xml')) . '">';

        // JSON-LD
        if (!$noindex) {
            $graph = is_array($v['jsonLd'] ?? null) ? $v['jsonLd'] : [];
            $breadcrumb = is_array($v['breadcrumb'] ?? null) ? $v['breadcrumb'] : [];
            if (count($breadcrumb) > 1) {
                $graph[] = self::breadcrumbSchema($breadcrumb, $canonical);
            }
            foreach ($graph as $schema) {
                $tags[] = self::jsonLd($schema);
            }
        }

        return implode("\n", $tags) . "\n";
    }

    // ------------------------------------------------------------------
    // JSON-LD builders
    // ------------------------------------------------------------------

    /** @param array<string, mixed> $site @return array<string, mixed> */
    public static function personSchema(array $site): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            '@id' => url('/') . '#person',
            'name' => (string) ($site['authorName'] ?? $site['name'] ?? ''),
            'url' => url('/'),
            'jobTitle' => 'Biên kịch',
            'description' => (string) ($site['authorBio'] ?? ''),
            'sameAs' => array_values(array_filter((array) ($site['social'] ?? []))),
        ], static fn ($value) => $value !== '' && $value !== []);
    }

    /** @param array<string, mixed> $site @return array<string, mixed> */
    public static function websiteSchema(array $site): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => url('/') . '#website',
            'name' => (string) ($site['name'] ?? ''),
            'url' => url('/'),
            'inLanguage' => 'vi',
            'publisher' => ['@id' => url('/') . '#person'],
        ];
    }

    /**
     * @param array<string, mixed> $post  Bản ghi từ Post::findPublished
     * @param array<string, mixed> $site
     * @return array<string, mixed>
     */
    public static function blogPostingSchema(array $post, array $site, string $description): array
    {
        $postUrl = url((string) $post['url']);
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => self::limit((string) $post['title'], 110),
            'description' => $description,
            'datePublished' => self::isoDate((string) $post['published_at']),
            'dateModified' => self::isoDate((string) ($post['updated_at'] ?: $post['published_at'])),
            'author' => ['@type' => 'Person', 'name' => (string) ($site['authorName'] ?? ''), 'url' => url('/gioi-thieu')],
            'publisher' => ['@id' => url('/') . '#person'],
            'image' => [self::defaultImage($site)],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $postUrl],
            'url' => $postUrl,
            'inLanguage' => 'vi',
            'articleSection' => (string) ($post['category_name'] ?? ''),
            'keywords' => implode(', ', array_map(static fn (array $t): string => (string) $t['name'], (array) ($post['tags'] ?? []))),
        ];
    }

    /**
     * @param array<string, mixed> $film  Bản ghi từ Film::findBySlug
     * @param array<string, mixed> $site
     * @return array<string, mixed>
     */
    public static function movieSchema(array $film, array $site): array
    {
        $author = ['@type' => 'Person', 'name' => (string) ($site['authorName'] ?? ''), 'url' => url('/gioi-thieu')];
        $awards = [];
        foreach (preg_split('/\R/u', (string) ($film['awards_md'] ?? '')) ?: [] as $line) {
            $line = trim(ltrim(trim($line), '-*'));
            if ($line !== '') {
                $awards[] = $line;
            }
        }
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Movie',
            'name' => (string) $film['title'],
            'alternateName' => (string) ($film['title_en'] ?? ''),
            'url' => url((string) $film['url']),
            'description' => (string) ($film['logline'] ?? ''),
            'image' => self::defaultImage($site),
            'genre' => (string) ($film['genre'] ?? ''),
            'duration' => !empty($film['duration_min']) ? 'PT' . (int) $film['duration_min'] . 'M' : '',
            'dateCreated' => !empty($film['year']) ? (string) $film['year'] : '',
            'inLanguage' => 'vi',
            'author' => $author,
            'actor' => $film['status'] === 'released' ? $author : [],
            'award' => $awards,
        ], static fn ($value) => $value !== '' && $value !== []);
    }

    /**
     * @param list<array{label: string, url?: string}> $items
     * @return array<string, mixed>
     */
    public static function breadcrumbSchema(array $items, string $currentUrl): array
    {
        $list = [];
        foreach (array_values($items) as $i => $item) {
            $list[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => (string) $item['label'],
                'item' => !empty($item['url']) ? url((string) $item['url']) : $currentUrl,
            ];
        }
        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list];
    }

    // ------------------------------------------------------------------
    // Tiện ích
    // ------------------------------------------------------------------

    /**
     * Ảnh chia sẻ mặc định (setting default_og_image hoặc ảnh poster minh họa của theme), URL tuyệt đối.
     *
     * @param array<string, mixed> $site
     */
    public static function defaultImage(array $site): string
    {
        $configured = self::absolute((string) ($site['ogImage'] ?? ''));
        return $configured !== '' ? $configured : asset('img/og-default.png');
    }

    /** ISO 8601 có múi giờ, cho JSON-LD/OG. Chuỗi rỗng nếu không parse được. */
    public static function isoDate(string $datetime): string
    {
        $ts = strtotime($datetime);
        return $ts === false ? '' : date('c', $ts);
    }

    private static function absolute(string $pathOrUrl): string
    {
        if ($pathOrUrl === '') {
            return '';
        }
        return preg_match('#^https?://#i', $pathOrUrl) ? $pathOrUrl : url($pathOrUrl);
    }

    private static function meta(string $attr, string $name, string $content): string
    {
        return '<meta ' . $attr . '="' . e($name) . '" content="' . e($content) . '">';
    }

    /** @param array<string, mixed> $schema */
    private static function jsonLd(array $schema): string
    {
        // JSON_HEX_TAG: "<" và ">" thành </> để chuỗi "</script>" trong dữ liệu không đóng thẻ
        $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
        return $json === false ? '' : '<script type="application/ld+json">' . $json . '</script>';
    }

    /** Cắt theo từ, kết quả (kể cả dấu "…") không vượt $max ký tự. */
    private static function limit(string $text, int $max): string
    {
        return mb_strlen($text) <= $max ? $text : Markdown::excerpt($text, $max - 1);
    }
}
