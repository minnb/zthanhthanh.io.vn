<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Ép một host chuẩn theo base_url trong config (HTTPS + có/không www) – chỉ ở production.
 * Không cần viết cứng tên miền vào .htaccess.
 * Tắt nhanh bằng config 'force_canonical_host' => false nếu hosting báo HTTPS theo cách lạ gây vòng lặp.
 */
final class CanonicalHost
{
    /** URL cần chuyển hướng tới, hoặc null nếu request đã đúng scheme + host. */
    public static function redirectUrl(string $path, string $query): ?string
    {
        if (Config::isLocal() || !Config::get('force_canonical_host', true)) {
            return null;
        }
        $base = parse_url((string) Config::get('base_url', ''));
        $wantScheme = strtolower((string) ($base['scheme'] ?? ''));
        $wantHost = strtolower((string) ($base['host'] ?? ''));
        if ($wantScheme === '' || $wantHost === '') {
            return null; // cấu hình thiếu: không đoán
        }
        $wantHost .= isset($base['port']) ? ':' . $base['port'] : '';

        $scheme = self::isHttps() ? 'https' : 'http';
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

        if ($scheme === $wantScheme && $host === $wantHost) {
            return null;
        }
        return url(encode_path($path)) . ($query !== '' ? '?' . $query : '');
    }

    /**
     * Request có đi qua HTTPS không. Ngoài $_SERVER['HTTPS'], nhận các dấu hiệu proxy/CDN phổ biến
     * trên shared hosting (SSL kết thúc ở proxy) để tránh vòng lặp https -> https.
     */
    public static function isHttps(): bool
    {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off') {
            return true;
        }
        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
            return true;
        }
        if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on') {
            return true;
        }
        // Cloudflare: CF-Visitor: {"scheme":"https"}
        return str_contains((string) ($_SERVER['HTTP_CF_VISITOR'] ?? ''), '"https"');
    }
}
