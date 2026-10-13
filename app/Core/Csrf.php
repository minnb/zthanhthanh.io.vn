<?php
declare(strict_types=1);

namespace App\Core;

/** Chống CSRF: một token mỗi session, gửi qua field "_csrf" (form) hoặc header "X-CSRF-Token" (API). */
final class Csrf
{
    private const KEY = '_csrf';

    public static function token(): string
    {
        $token = Session::get(self::KEY);
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::KEY, $token);
        }
        return $token;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    /** Ném 403 nếu token thiếu/sai. Gọi cho MỌI request POST trong admin (security.md). */
    public static function verify(): void
    {
        $sent = Request::str('_csrf');
        if ($sent === '') {
            $sent = Request::header('X-CSRF-Token');
        }
        $expected = Session::get(self::KEY);
        if (!is_string($expected) || $sent === '' || !hash_equals($expected, $sent)) {
            throw new HttpException(403, 'Phiên làm việc đã hết hạn hoặc không hợp lệ. Hãy tải lại trang rồi thử lại.');
        }
    }
}
