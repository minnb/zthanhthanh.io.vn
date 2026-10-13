<?php
declare(strict_types=1);

namespace App\Core;

use Parsedown;

/**
 * Render Markdown -> HTML an toàn (Parsedown safeMode: escape HTML thô, chặn link javascript:).
 * Xuống dòng đơn giữ nguyên thành <br> vì tác giả quen viết kiểu Facebook.
 */
final class Markdown
{
    private static ?Parsedown $parser = null;

    public static function toHtml(string $markdown): string
    {
        return self::render($markdown, true);
    }

    /** $enrichImages = false: bỏ bước tra DB ảnh (toText chỉ cần chữ – tránh N+1 ở danh sách bài). */
    private static function render(string $markdown, bool $enrichImages): string
    {
        if (self::$parser === null) {
            require_once APP_ROOT . '/app/Lib/Parsedown.php';
            self::$parser = (new Parsedown())->setSafeMode(true)->setBreaksEnabled(true);
        }
        $html = self::$parser->text($markdown);

        if ($enrichImages) {
            $html = self::enrichLibraryImages($html);
        }
        // Ảnh thư viện chèn vào bài lưu dạng "/uploads/..." (độc lập domain) -> URL đầy đủ theo base_url
        $html = str_replace(' src="/uploads/', ' src="' . url('/uploads/'), $html);
        // Link nội bộ "/phim/doi" -> URL đầy đủ (site có thể nằm trong thư mục con, vd XAMPP local)
        $html = (string) preg_replace_callback(
            '#<a href="(/(?!/)[^"]*)"#',
            // decode để url() xử lý đúng, rồi escape lại bằng e() trước khi đặt vào thuộc tính (chống XSS)
            static fn (array $m): string => '<a href="' . e(url(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'))) . '"',
            $html
        );
        // Ảnh trong bài: tải lười, giải mã bất đồng bộ (ảnh LCP của trang là ảnh bìa, không nằm trong bài)
        return str_replace('<img ', '<img loading="lazy" decoding="async" ', $html);
    }

    /**
     * Ảnh thư viện chèn trong nội dung (`<img src="/uploads/<path>-<rộng>.webp">`): gắn width/height (chống nhảy
     * bố cục – CLS) và srcset/sizes (điện thoại tải bản 480). Tra media bằng một truy vấn; ảnh ngoài thư viện giữ nguyên.
     */
    private static function enrichLibraryImages(string $html): string
    {
        $pattern = '#<img src="/uploads/([0-9]{4}/[0-9]{2}/[a-z0-9-]+)-[0-9]+\.webp"#';
        if (!preg_match_all($pattern, $html, $found)) {
            return $html;
        }
        $media = \App\Models\Media::findByPaths(array_values(array_unique($found[1])));
        if ($media === []) {
            return $html;
        }
        return (string) preg_replace_callback($pattern, static function (array $m) use ($media): string {
            $row = $media[$m[1]] ?? null;
            if ($row === null) {
                return $m[0];
            }
            $img = \App\Models\Media::imageData($row);
            return '<img src="' . e($img['src']) . '" srcset="' . e($img['srcset']) . '" sizes="(min-width: 760px) 720px, 100vw"'
                . ' width="' . $img['width'] . '" height="' . $img['height'] . '"';
        }, $html);
    }

    /** Văn bản thuần (bỏ cú pháp Markdown) – dùng cho excerpt, meta description, đếm từ. */
    public static function toText(string $markdown): string
    {
        return self::htmlToText(self::render($markdown, false));
    }

    /** Văn bản thuần từ HTML đã render (dùng lại kết quả toHtml, tránh render lần 2). */
    public static function htmlToText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /** Số phút đọc ước tính (~200 từ/phút với tiếng Việt), tối thiểu 1. */
    public static function readingMinutes(string $markdown): int
    {
        return self::readingMinutesFromText(self::toText($markdown));
    }

    public static function readingMinutesFromText(string $text): int
    {
        $words = count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);
        return max(1, (int) round($words / 200));
    }

    /** Cắt văn bản theo số ký tự, không cắt giữa từ, thêm "…". */
    public static function excerpt(string $text, int $length = 160): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        $cut = mb_substr($text, 0, $length);
        $space = mb_strrpos($cut, ' ');
        return rtrim($space !== false ? mb_substr($cut, 0, $space) : $cut, " ,.;:–-") . '…';
    }
}
