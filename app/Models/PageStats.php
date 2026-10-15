<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

/**
 * Thống kê lượt truy cập: ghi từ beacon POST /t (StatsController), đọc cho trang admin Thống kê.
 * Chỉ lưu số tổng hợp theo ngày. Không lưu IP: "người xem" = băm(salt đổi mỗi ngày + IP + UA),
 * salt cũ bị ghi đè nên mã băm ngày trước không tính lại được; bảng mã băm chỉ giữ 2 ngày.
 */
final class PageStats
{
    /** Một người xem tính tối đa ngần này lượt cho một trang / cả site trong một ngày (chống bơm số). */
    public const MAX_PAGE_HITS = 20;
    public const MAX_DAY_HITS = 300;
    /** Một IP tối đa ngần này lượt/ngày dù đổi User-Agent (nhiều người chung mạng công ty/trường vẫn đủ). */
    public const MAX_IP_HITS = 1000;
    /** Mỗi ngày nhận tối đa ngần này nguồn khác nhau; vượt thì gom vào "other" (chống spam referrer). */
    public const MAX_SOURCES_PER_DAY = 50;
    /**
     * Dòng đặc biệt trong page_visitors_daily (không trùng path công khai vì normalizePath không cho "*", "#"):
     * SITE_ROW = tổng lượt của một người trong ngày, IP_ROW = tổng lượt của một IP trong ngày.
     */
    private const SITE_ROW = '*';
    private const IP_ROW = '#ip';
    /** Giữ mã băm người xem của hôm nay và hôm qua. */
    private const KEEP_VISITOR_DAYS = 2;

    /** Trang công khai có route cố định (routes.php). */
    private const STATIC_TITLES = [
        '/' => 'Trang chủ',
        '/phim' => 'Danh sách phim',
        '/gioi-thieu' => 'Giới thiệu',
        '/lien-he' => 'Liên hệ',
        '/goc-sang-tac' => 'Không gian sáng tác',
        '/kich-ban' => 'Kịch bản (Không gian sáng tác)',
        '/tu-sach' => 'Tủ sách (Không gian sáng tác)',
        '/en/press-kit' => 'Press kit (EN)',
    ];

    /** Nguồn truy cập => tên miền (khớp đúng hoặc tên miền con). */
    private const SOURCES = [
        'facebook' => ['facebook.com', 'fb.com', 'fb.me'],
        'messenger' => ['messenger.com'],
        'google' => ['google.com', 'google.com.vn'],
        'zalo' => ['zalo.me', 'zaloapp.com', 'zalo.vn'],
        'youtube' => ['youtube.com', 'youtu.be'],
        'tiktok' => ['tiktok.com'],
        'instagram' => ['instagram.com'],
        'threads' => ['threads.net', 'threads.com'],
        'bing' => ['bing.com'],
        'coccoc' => ['coccoc.com'],
    ];

    public const SOURCE_LABELS = [
        'direct' => 'Trực tiếp / không rõ',
        'internal' => 'Chuyển trang trong website',
        'facebook' => 'Facebook',
        'messenger' => 'Messenger',
        'google' => 'Google',
        'zalo' => 'Zalo',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'instagram' => 'Instagram',
        'threads' => 'Threads',
        'bing' => 'Bing',
        'coccoc' => 'Cốc Cốc',
        'other' => 'Nguồn khác',
    ];

    /**
     * Chuẩn hóa path từ beacon: bỏ query/fragment, bỏ base path, bỏ "/" cuối.
     * Trả null nếu không giống URL công khai (chỉ chữ thường không dấu, số, gạch nối, tối đa 2 đoạn).
     */
    public static function normalizePath(string $raw, string $basePath): ?string
    {
        $path = (string) parse_url($raw, PHP_URL_PATH);
        if ($basePath !== '' && str_starts_with($path, $basePath . '/')) {
            $path = substr($path, strlen($basePath));
        } elseif ($basePath !== '' && $path === $basePath) {
            $path = '/';
        }
        $path = $path === '/' ? '/' : rtrim($path, '/');
        if ($path === '/' || preg_match('#^(/[a-z0-9]+(?:-[a-z0-9]+)*){1,2}$#', $path) === 1) {
            return strlen($path) <= 255 ? $path : null;
        }
        return null;
    }

    /** Path đã chuẩn hóa có phải trang công khai đang tồn tại không (tránh ghi path rác). */
    public static function isPublicPath(string $path): bool
    {
        if (isset(self::STATIC_TITLES[$path])) {
            return true;
        }
        $parts = explode('/', ltrim($path, '/'));
        if (count($parts) === 1) {
            return Db::value('SELECT 1 FROM categories WHERE slug = :s', ['s' => $parts[0]]) !== null;
        }
        [$first, $slug] = $parts;
        return match ($first) {
            'phim' => Db::value('SELECT 1 FROM films WHERE slug = :s', ['s' => $slug]) !== null,
            'chu-de' => Db::value('SELECT 1 FROM tags WHERE slug = :s', ['s' => $slug]) !== null,
            default => Db::value(
                "SELECT 1 FROM posts p JOIN categories c ON c.id = p.category_id
                 WHERE p.slug = :s AND c.slug = :c AND p.status = 'published'
                   AND p.deleted_at IS NULL AND p.published_at <= NOW()",
                ['s' => $slug, 'c' => $first]
            ) !== null,
        };
    }

    /**
     * Gom referrer thành nguồn: direct | internal | facebook | google | ... | tên miền khác.
     * $hasFbclid: URL có fbclid (trình duyệt trong app Facebook thường không gửi referrer).
     */
    public static function source(string $referrer, string $siteHost, bool $hasFbclid = false): string
    {
        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));
        if ($host === '') {
            return $hasFbclid ? 'facebook' : 'direct';
        }
        $host = preg_replace('/^(www|m|l|lm|mobile|mbasic)\./', '', $host) ?? $host;
        $site = preg_replace('/^www\./', '', strtolower($siteHost)) ?? $siteHost;
        if ($site !== '' && $host === $site) {
            return 'internal';
        }
        foreach (self::SOURCES as $name => $domains) {
            foreach ($domains as $domain) {
                if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                    return $name;
                }
            }
        }
        // Google theo quốc gia: google.co.uk, google.de...
        if (preg_match('/^google\.[a-z.]{2,6}$/', $host) === 1) {
            return 'google';
        }
        return preg_match('/^[a-z0-9.-]{1,100}$/', $host) === 1 ? $host : 'direct';
    }

    /**
     * Mã người xem (IP + UA) và mã IP trong ngày: 32 ký tự hex, không đảo ngược được sau khi salt đổi.
     *
     * @return array{visitor: string, ip: string}
     */
    public static function hashes(string $ip, string $userAgent, string $day): array
    {
        $salt = self::salt($day);
        return [
            'visitor' => substr(hash('sha256', $salt . '|' . $ip . '|' . $userAgent), 0, 32),
            'ip' => substr(hash('sha256', $salt . '|ip|' . $ip), 0, 32),
        ];
    }

    /**
     * Ghi một lượt xem. Trả false nếu bỏ qua do vượt giới hạn.
     */
    public static function record(string $day, string $path, string $visitor, string $ipKey, string $source): bool
    {
        $hits = ['page' => 0, 'site' => 0, 'ip' => 0];
        $rows = Db::all(
            'SELECT visitor, path, hits FROM page_visitors_daily
             WHERE day = :d AND ((visitor = :v AND path IN (:p, :site)) OR (visitor = :ipk AND path = :iprow))',
            ['d' => $day, 'v' => $visitor, 'p' => $path, 'site' => self::SITE_ROW, 'ipk' => $ipKey, 'iprow' => self::IP_ROW]
        );
        foreach ($rows as $row) {
            $key = match ((string) $row['path']) {
                self::SITE_ROW => 'site',
                self::IP_ROW => 'ip',
                default => 'page',
            };
            $hits[$key] = (int) $row['hits'];
        }
        if ($hits['page'] >= self::MAX_PAGE_HITS || $hits['site'] >= self::MAX_DAY_HITS || $hits['ip'] >= self::MAX_IP_HITS) {
            return false;
        }

        // rowCount() của INSERT … ON DUPLICATE KEY UPDATE: 1 = dòng mới (lần đầu trong ngày), 2 = đã có.
        // Nguyên tử theo khóa UNIQUE nên 2 beacon song song không cùng được tính là "người mới".
        $newOnPage = self::bump($day, $visitor, $path) === 1 ? 1 : 0;
        $newOnSite = self::bump($day, $visitor, self::SITE_ROW) === 1 ? 1 : 0;
        self::bump($day, $ipKey, self::IP_ROW);

        Db::query(
            'INSERT INTO page_views_daily (day, path, views, visitors) VALUES (:d, :p, 1, :n)
             ON DUPLICATE KEY UPDATE views = views + 1, visitors = visitors + :n2',
            ['d' => $day, 'p' => $path, 'n' => $newOnPage, 'n2' => $newOnPage]
        );
        Db::query(
            'INSERT INTO site_daily (day, views, visitors) VALUES (:d, 1, :n)
             ON DUPLICATE KEY UPDATE views = views + 1, visitors = visitors + :n2',
            ['d' => $day, 'n' => $newOnSite, 'n2' => $newOnSite]
        );
        Db::query(
            'INSERT INTO referrers_daily (day, source, views) VALUES (:d, :s, 1)
             ON DUPLICATE KEY UPDATE views = views + 1',
            ['d' => $day, 's' => self::capSource($day, mb_substr($source, 0, 100))]
        );
        return true;
    }

    /** Tăng bộ đếm một dòng page_visitors_daily; trả rowCount (1 = vừa tạo). */
    private static function bump(string $day, string $visitor, string $path): int
    {
        return Db::query(
            'INSERT INTO page_visitors_daily (day, visitor, path, hits) VALUES (:d, :v, :p, 1)
             ON DUPLICATE KEY UPDATE hits = hits + 1',
            ['d' => $day, 'v' => $visitor, 'p' => $path]
        )->rowCount();
    }

    /** Nguồn tên miền lạ mới khi hôm nay đã đủ MAX_SOURCES_PER_DAY nguồn -> "other". Nguồn đã biết luôn giữ nguyên. */
    private static function capSource(string $day, string $source): string
    {
        if (isset(self::SOURCE_LABELS[$source])) {
            return $source;
        }
        $row = Db::one(
            'SELECT COUNT(*) AS n, COALESCE(MAX(source = :s), 0) AS has FROM referrers_daily WHERE day = :d',
            ['s' => $source, 'd' => $day]
        );
        if ((int) ($row['has'] ?? 0) === 1 || (int) ($row['n'] ?? 0) < self::MAX_SOURCES_PER_DAY) {
            return $source;
        }
        return 'other';
    }

    /** Xóa mã băm người xem cũ (gọi ngẫu nhiên khi ghi, không cần cron). */
    public static function prune(string $today): void
    {
        $cutoff = date('Y-m-d', (int) strtotime($today . ' -' . (self::KEEP_VISITOR_DAYS - 1) . ' day'));
        Db::query('DELETE FROM page_visitors_daily WHERE day < :d', ['d' => $cutoff]);
    }

    /** @return array{views: int, visitors: int} */
    public static function totals(string $from, string $to): array
    {
        $row = Db::one(
            'SELECT COALESCE(SUM(views), 0) AS views, COALESCE(SUM(visitors), 0) AS visitors
             FROM site_daily WHERE day BETWEEN :f AND :t',
            ['f' => $from, 't' => $to]
        );
        return ['views' => (int) ($row['views'] ?? 0), 'visitors' => (int) ($row['visitors'] ?? 0)];
    }

    /**
     * Số liệu từng ngày, ngày không có lượt xem = 0.
     *
     * @return list<array{day: string, views: int, visitors: int}>
     */
    public static function daily(string $from, string $to): array
    {
        $byDay = [];
        foreach (Db::all('SELECT day, views, visitors FROM site_daily WHERE day BETWEEN :f AND :t', ['f' => $from, 't' => $to]) as $row) {
            $byDay[(string) $row['day']] = $row;
        }
        $days = [];
        $end = strtotime($to);
        for ($t = strtotime($from); $t !== false && $end !== false && $t <= $end; $t = strtotime('+1 day', $t)) {
            $day = date('Y-m-d', $t);
            $days[] = [
                'day' => $day,
                'views' => (int) ($byDay[$day]['views'] ?? 0),
                'visitors' => (int) ($byDay[$day]['visitors'] ?? 0),
            ];
        }
        return $days;
    }

    /**
     * Trang xem nhiều nhất, kèm tiêu đề.
     *
     * @return list<array{path: string, title: string, views: int, visitors: int}>
     */
    public static function topPages(string $from, string $to, int $limit = 20): array
    {
        $rows = Db::all(
            'SELECT path, SUM(views) AS views, SUM(visitors) AS visitors FROM page_views_daily
             WHERE day BETWEEN :f AND :t GROUP BY path ORDER BY views DESC, path LIMIT ' . max(1, min(100, $limit)),
            ['f' => $from, 't' => $to]
        );
        $titles = self::titles(array_map(static fn (array $r): string => (string) $r['path'], $rows));
        return array_map(static fn (array $r): array => [
            'path' => (string) $r['path'],
            'title' => $titles[(string) $r['path']] ?? (string) $r['path'],
            'views' => (int) $r['views'],
            'visitors' => (int) $r['visitors'],
        ], $rows);
    }

    /** @return list<array{source: string, label: string, views: int}> */
    public static function topSources(string $from, string $to, int $limit = 12): array
    {
        $rows = Db::all(
            'SELECT source, SUM(views) AS views FROM referrers_daily
             WHERE day BETWEEN :f AND :t GROUP BY source ORDER BY views DESC, source LIMIT ' . max(1, min(50, $limit)),
            ['f' => $from, 't' => $to]
        );
        return array_map(static fn (array $r): array => [
            'source' => (string) $r['source'],
            'label' => self::SOURCE_LABELS[(string) $r['source']] ?? (string) $r['source'],
            'views' => (int) $r['views'],
        ], $rows);
    }

    /**
     * Tiêu đề cho danh sách path, mỗi loại một truy vấn (không N+1).
     *
     * @param list<string> $paths
     * @return array<string, string>
     */
    private static function titles(array $paths): array
    {
        $titles = [];
        $groups = ['cat' => [], 'film' => [], 'tag' => [], 'post' => []];
        foreach ($paths as $path) {
            if (isset(self::STATIC_TITLES[$path])) {
                $titles[$path] = self::STATIC_TITLES[$path];
                continue;
            }
            $parts = explode('/', ltrim($path, '/'));
            if (count($parts) === 1) {
                $groups['cat'][] = $parts[0];
            } elseif ($parts[0] === 'phim') {
                $groups['film'][] = $parts[1];
            } elseif ($parts[0] === 'chu-de') {
                $groups['tag'][] = $parts[1];
            } elseif (count($parts) === 2) {
                $groups['post'][] = $parts[1];
            }
        }
        // Trang tĩnh trong bảng pages: lấy tiêu đề admin đã đặt
        foreach (self::lookup('SELECT slug, lang, title FROM pages WHERE slug IN (%s)', ['gioi-thieu', 'lien-he', 'press-kit']) as $r) {
            $path = Page::path($r);
            if (isset($titles[$path])) {
                $titles[$path] = (string) $r['title'] . ($r['lang'] !== 'vi' ? ' (' . strtoupper((string) $r['lang']) . ')' : '');
            }
        }
        foreach (self::lookup('SELECT slug, name FROM categories WHERE slug IN (%s)', $groups['cat']) as $r) {
            $titles['/' . $r['slug']] = 'Chuyên mục: ' . $r['name'];
        }
        foreach (self::lookup('SELECT slug, title FROM films WHERE slug IN (%s)', $groups['film']) as $r) {
            $titles['/phim/' . $r['slug']] = 'Phim: ' . $r['title'];
        }
        foreach (self::lookup('SELECT slug, name FROM tags WHERE slug IN (%s)', $groups['tag']) as $r) {
            $titles['/chu-de/' . $r['slug']] = 'Chủ đề #' . $r['name'];
        }
        $sql = 'SELECT p.slug, p.title, c.slug AS category_slug FROM posts p
                JOIN categories c ON c.id = p.category_id WHERE p.slug IN (%s)';
        foreach (self::lookup($sql, $groups['post']) as $r) {
            $titles['/' . $r['category_slug'] . '/' . $r['slug']] = (string) $r['title'];
        }
        return $titles;
    }

    /**
     * Chạy truy vấn "... IN (%s)" với danh sách giá trị (placeholder sinh tự động, giá trị vẫn bind).
     *
     * @param list<string> $values
     * @return list<array<string, mixed>>
     */
    private static function lookup(string $sql, array $values): array
    {
        $values = array_values(array_unique($values));
        if ($values === []) {
            return [];
        }
        $params = [];
        foreach ($values as $i => $value) {
            $params['v' . $i] = $value;
        }
        $placeholders = implode(', ', array_map(static fn (string $k): string => ':' . $k, array_keys($params)));
        return Db::all(sprintf($sql, $placeholders), $params);
    }

    /**
     * Salt của ngày, lưu trong settings dạng "YYYY-MM-DD:hex". Sang ngày mới thì sinh salt mới, ghi đè salt cũ.
     * Ghi có điều kiện trong MỘT câu lệnh (khóa dòng): nhiều request cùng lúc đầu ngày chỉ một salt thắng,
     * các request còn lại đọc lại đúng salt đó -> cùng một người không bị tính 2 lần.
     */
    private static function salt(string $day): string
    {
        $stored = (string) Setting::get('stats_salt', '');
        if (str_starts_with($stored, $day . ':') && strlen($stored) > strlen($day) + 1) {
            return substr($stored, strlen($day) + 1);
        }
        Db::query(
            "INSERT INTO settings (`key`, `value`) VALUES ('stats_salt', :v)
             ON DUPLICATE KEY UPDATE `value` = IF(LEFT(`value`, 11) = :prefix, `value`, VALUES(`value`))",
            ['v' => $day . ':' . bin2hex(random_bytes(16)), 'prefix' => $day . ':']
        );
        $stored = (string) Db::value("SELECT `value` FROM settings WHERE `key` = 'stats_salt'");
        if (!str_starts_with($stored, $day . ':')) {
            throw new \RuntimeException('Không lưu được salt thống kê.');
        }
        return substr($stored, strlen($day) + 1);
    }
}
