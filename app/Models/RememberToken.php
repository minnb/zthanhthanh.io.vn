<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

/**
 * "Ghi nhớ đăng nhập": cookie chứa selector:validator. DB chỉ lưu sha256(validator),
 * nên lộ DB cũng không dùng được cookie.
 */
final class RememberToken
{
    /** @return array{selector: string, validator: string, expires: int} */
    public static function issue(int $userId, int $days): array
    {
        $selector = bin2hex(random_bytes(12));   // 24 ký tự (CHAR(24))
        $validator = bin2hex(random_bytes(32));
        $expires = time() + $days * 86400;
        Db::query(
            'INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at)
             VALUES (:uid, :sel, :hash, :exp)',
            ['uid' => $userId, 'sel' => $selector, 'hash' => hash('sha256', $validator), 'exp' => date('Y-m-d H:i:s', $expires)]
        );
        return ['selector' => $selector, 'validator' => $validator, 'expires' => $expires];
    }

    /** User id nếu cặp selector/validator hợp lệ và chưa hết hạn. */
    public static function validate(string $selector, string $validator): ?int
    {
        if (!preg_match('/^[a-f0-9]{24}$/', $selector) || !preg_match('/^[a-f0-9]{64}$/', $validator)) {
            return null;
        }
        $row = Db::one(
            'SELECT user_id, token_hash FROM remember_tokens WHERE selector = :sel AND expires_at > NOW()',
            ['sel' => $selector]
        );
        if ($row === null || !hash_equals((string) $row['token_hash'], hash('sha256', $validator))) {
            return null;
        }
        return (int) $row['user_id'];
    }

    public static function delete(string $selector): void
    {
        Db::query('DELETE FROM remember_tokens WHERE selector = :sel', ['sel' => $selector]);
    }

    /**
     * Hết hạn sau $seconds giây (thay vì xóa ngay khi xoay vòng): request song song cùng dùng token cũ
     * (vd trang + tự lưu lúc session vừa hết) vẫn đăng nhập được, không bị đăng xuất oan.
     */
    public static function retire(string $selector, int $seconds = 30): void
    {
        Db::query(
            'UPDATE remember_tokens SET expires_at = LEAST(expires_at, NOW() + INTERVAL :s SECOND) WHERE selector = :sel',
            ['s' => $seconds, 'sel' => $selector]
        );
    }

    /** Thu hồi mọi token của user (đổi mật khẩu, "Đăng xuất mọi thiết bị"). */
    public static function deleteForUser(int $userId): void
    {
        Db::query('DELETE FROM remember_tokens WHERE user_id = :uid', ['uid' => $userId]);
    }

    public static function pruneExpired(): void
    {
        Db::query('DELETE FROM remember_tokens WHERE expires_at <= NOW()');
    }
}
