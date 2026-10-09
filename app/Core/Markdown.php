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
        if (self::$parser === null) {
            require_once APP_ROOT . '/app/Lib/Parsedown.php';
            self::$parser = (new Parsedown())->setSafeMode(true)->setBreaksEnabled(true);
        }
        $html = self::$parser->text($markdown);

        // Ảnh trong bài: tải lười, giải mã bất đồng bộ (ảnh LCP của trang là ảnh bìa, không nằm trong bài)
        return str_replace('<img ', '<img loading="lazy" decoding="async" ', $html);
    }

    /** Văn bản thuần (bỏ cú pháp Markdown) – dùng cho excerpt, meta description, đếm từ. */
    public static function toText(string $markdown): string
    {
        return self::htmlToText(self::toHtml($markdown));
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
