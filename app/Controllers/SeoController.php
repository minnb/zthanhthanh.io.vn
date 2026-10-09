<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Seo;
use App\Models\Category;
use App\Models\Film;
use App\Models\Page;
use App\Models\Post;
use App\Models\Site;
use App\Models\Tag;

/** /sitemap.xml, /robots.txt, /feed.xml */
final class SeoController
{
    /** Sitemap chỉ gồm trang được index (không có trang noindex, nháp, dev, admin). */
    public function sitemap(): string
    {
        header('Content-Type: application/xml; charset=utf-8');

        $entries = [['/', Post::lastModified()]];
        foreach (Category::all() as $cat) {
            $lastmod = Post::lastModified((int) $cat['id']);
            if ($lastmod !== null) { // danh mục chưa có bài là noindex -> không đưa vào sitemap
                $entries[] = ['/' . $cat['slug'], $lastmod];
            }
        }
        foreach (Post::forSitemap() as $post) {
            $entries[] = [$post['url'], $post['lastmod']];
        }
        foreach (Tag::indexable(3) as $tag) {
            $entries[] = ['/chu-de/' . $tag['slug'], null];
        }
        $entries[] = ['/phim', null];
        foreach (Film::all() as $film) {
            $entries[] = [(string) $film['url'], (string) $film['updated_at']];
        }
        foreach (Page::allPublished() as $page) {
            $entries[] = [Page::path($page), (string) $page['updated_at']];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($entries as [$path, $lastmod]) {
            $xml .= '  <url><loc>' . self::xml(url($path)) . '</loc>';
            $iso = $lastmod ? Seo::isoDate($lastmod) : '';
            if ($iso !== '') {
                $xml .= '<lastmod>' . $iso . '</lastmod>';
            }
            $xml .= "</url>\n";
        }
        return $xml . "</urlset>\n";
    }

    public function robots(): string
    {
        header('Content-Type: text/plain; charset=utf-8');
        $base = Config::basePath();
        return "User-agent: *\n"
            . "Disallow: {$base}/admin\n"
            . "Disallow: {$base}/_dev\n"
            . "Allow: {$base}/\n\n"
            . 'Sitemap: ' . url('/sitemap.xml') . "\n";
    }

    /** RSS 2.0 cho 20 bài mới nhất. */
    public function feed(): string
    {
        header('Content-Type: application/rss+xml; charset=utf-8');
        $site = Site::info();
        $posts = Post::forFeed(20);
        $lastBuild = $posts !== [] ? (string) $posts[0]['published_at'] : 'now';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n<channel>\n"
            . '  <title>' . self::xml((string) $site['name']) . "</title>\n"
            . '  <link>' . self::xml(url('/')) . "</link>\n"
            . '  <description>' . self::xml((string) ($site['mission'] ?: $site['tagline'])) . "</description>\n"
            . "  <language>vi</language>\n"
            . '  <lastBuildDate>' . date(DATE_RSS, strtotime($lastBuild) ?: time()) . "</lastBuildDate>\n"
            . '  <atom:link href="' . self::xml(url('/feed.xml')) . '" rel="self" type="application/rss+xml"/>' . "\n";
        foreach ($posts as $post) {
            $link = url((string) $post['url']);
            $xml .= "  <item>\n"
                . '    <title>' . self::xml((string) $post['title']) . "</title>\n"
                . '    <link>' . self::xml($link) . "</link>\n"
                . '    <guid isPermaLink="true">' . self::xml($link) . "</guid>\n"
                . '    <pubDate>' . date(DATE_RSS, strtotime((string) $post['published_at']) ?: time()) . "</pubDate>\n"
                . '    <category>' . self::xml((string) $post['category']) . "</category>\n"
                . '    <description>' . self::xml((string) $post['excerpt']) . "</description>\n"
                . "  </item>\n";
        }
        return $xml . "</channel>\n</rss>\n";
    }

    private static function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
