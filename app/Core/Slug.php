<?php
declare(strict_types=1);

namespace App\Core;

/** Tạo slug không dấu từ tiếng Việt: "Phim ngắn ĐỢI" -> "phim-ngan-doi". */
final class Slug
{
    private const MAP = [
        'a' => 'àáạảãâầấậẩẫăằắặẳẵ',
        'e' => 'èéẹẻẽêềếệểễ',
        'i' => 'ìíịỉĩ',
        'o' => 'òóọỏõôồốộổỗơờớợởỡ',
        'u' => 'ùúụủũưừứựửữ',
        'y' => 'ỳýỵỷỹ',
        'd' => 'đ',
    ];

    /** @var array<string, string>|null */
    private static ?array $table = null;

    public static function make(string $text, int $maxLength = 80): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        // Gộp ký tự tổ hợp (dấu rời, hay gặp khi copy từ Facebook/macOS) về dạng dựng sẵn
        if (class_exists(\Normalizer::class)) {
            $text = (string) \Normalizer::normalize($text, \Normalizer::FORM_C);
        } else {
            $text = (string) preg_replace('/\p{Mn}+/u', '', $text);
        }
        $text = strtr($text, self::table());
        $text = (string) preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');

        if (strlen($text) > $maxLength) {
            $cut = substr($text, 0, $maxLength);
            $pos = strrpos($cut, '-');
            $text = $pos !== false && $pos > $maxLength / 2 ? substr($cut, 0, $pos) : $cut;
        }
        return trim($text, '-');
    }

    /** @return array<string, string> */
    private static function table(): array
    {
        if (self::$table === null) {
            self::$table = [];
            foreach (self::MAP as $ascii => $chars) {
                foreach (mb_str_split($chars, 1, 'UTF-8') as $char) {
                    self::$table[$char] = $ascii;
                }
            }
        }
        return self::$table;
    }
}
