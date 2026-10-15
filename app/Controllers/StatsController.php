<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\ErrorHandler;
use App\Core\Request;
use App\Models\PageStats;

/**
 * POST /t – beacon lượt xem từ app.js (chạy cả với trang lấy từ cache HTML).
 * Không phiên, không cookie. Luôn trả 204, kể cả khi bỏ qua hoặc lỗi (lỗi chỉ ghi log).
 */
final class StatsController
{
    private const MAX_BODY = 1024;
    // (?<!cu)bot: không nhầm điện thoại hãng Cubot (UA chứa "CUBOT")
    private const BOT_UA = '/(?<!cu)bot|crawl|spider|slurp|facebookexternalhit|headless|lighthouse|python-requests|curl|wget|httpclient|preview/i';
    /** Xác suất dọn mã băm cũ mỗi lượt ghi (1%). */
    private const PRUNE_PERCENT = 1;

    public function hit(): string
    {
        http_response_code(204);
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
        try {
            $this->collect();
        } catch (\Throwable $e) {
            ErrorHandler::log($e);
        }
        return '';
    }

    private function collect(): void
    {
        $ua = Request::header('User-Agent');
        if ($ua === '' || strlen($ua) > 512 || preg_match(self::BOT_UA, $ua) === 1) {
            return;
        }
        // Prefetch/prerender không phải lượt xem thật
        if (stripos(Request::header('Sec-Purpose') . Request::header('Purpose'), 'prefetch') !== false) {
            return;
        }
        // Chỉ nhận beacon từ chính website (trình duyệt luôn gửi Sec-Fetch-Site / Origin với POST)
        $fetchSite = Request::header('Sec-Fetch-Site');
        if ($fetchSite !== '' && $fetchSite !== 'same-origin') {
            return;
        }
        $siteHost = (string) parse_url((string) Config::get('base_url', ''), PHP_URL_HOST);
        $origin = Request::header('Origin');
        if ($origin !== '' && strcasecmp((string) parse_url($origin, PHP_URL_HOST), $siteHost) !== 0) {
            return;
        }

        $data = self::body();
        if ($data === null || !is_string($data['p'] ?? null)) {
            return;
        }
        $path = PageStats::normalizePath($data['p'], Config::basePath());
        if ($path === null || !PageStats::isPublicPath($path)) {
            return;
        }
        $referrer = is_string($data['r'] ?? null) ? $data['r'] : '';
        $source = PageStats::source($referrer, $siteHost, ($data['f'] ?? 0) === 1);

        $day = date('Y-m-d');
        $hashes = PageStats::hashes(Request::ip(), $ua, $day);
        PageStats::record($day, $path, $hashes['visitor'], $hashes['ip'], $source);
        if (random_int(1, 100) <= self::PRUNE_PERCENT) {
            PageStats::prune($day);
        }
    }

    /**
     * Body JSON nhỏ {p: path, r: referrer, f: 1 nếu URL có fbclid}. Quá 1KB hoặc sai định dạng -> null.
     *
     * @return array<string, mixed>|null
     */
    private static function body(): ?array
    {
        if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > self::MAX_BODY) {
            return null;
        }
        $raw = file_get_contents('php://input', false, null, 0, self::MAX_BODY + 1);
        if (!is_string($raw) || $raw === '' || strlen($raw) > self::MAX_BODY) {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }
}
