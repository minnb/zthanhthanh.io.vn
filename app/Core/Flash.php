<?php
declare(strict_types=1);

namespace App\Core;

/** Thông báo một lần (toast) giữa hai request: lưu vào session, đọc xong thì xóa. */
final class Flash
{
    private const KEY = '_flash';

    /** @param 'success'|'error'|'info' $type */
    public static function add(string $type, string $message): void
    {
        $items = Session::get(self::KEY, []);
        $items = is_array($items) ? $items : [];
        $items[] = ['type' => $type, 'message' => $message];
        Session::set(self::KEY, $items);
    }

    /** @return list<array{type: string, message: string}> */
    public static function pull(): array
    {
        $items = Session::get(self::KEY, []);
        Session::forget(self::KEY);
        return is_array($items) ? array_values($items) : [];
    }
}
