<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use Throwable;

/** Thông tin chẩn đoán DB cho trang /_dev/health (chỉ local). */
final class SystemInfo
{
    /** @return array{ok: bool, error: string, version: string, tables: array<string, int>} */
    public static function database(): array
    {
        $info = ['ok' => false, 'error' => '', 'version' => '', 'tables' => []];
        try {
            $info['version'] = (string) Db::value('SELECT VERSION()');
            foreach (Db::all('SHOW TABLES') as $row) {
                $table = (string) reset($row);
                // Tên bảng lấy từ SHOW TABLES và chỉ gồm a-z_, an toàn để đặt trong backtick
                if (preg_match('/^[a-z_]+$/', $table)) {
                    $info['tables'][$table] = (int) Db::value("SELECT COUNT(*) FROM `{$table}`");
                }
            }
            $info['ok'] = true;
        } catch (Throwable $e) {
            $info['error'] = $e->getMessage();
        }
        return $info;
    }
}
