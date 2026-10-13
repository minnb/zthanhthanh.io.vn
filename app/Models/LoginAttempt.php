<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

/** Nhật ký đăng nhập để giới hạn số lần sai (security.md). */
final class LoginAttempt
{
    /** Ghi một lần thử (mặc định coi là sai) – trả id để đánh dấu thành công sau khi kiểm mật khẩu. */
    public static function record(string $ip, string $email, bool $success = false): int
    {
        Db::query(
            'INSERT INTO login_attempts (ip, email, success) VALUES (:ip, :email, :ok)',
            ['ip' => $ip, 'email' => self::normalize($email), 'ok' => $success ? 1 : 0]
        );
        return (int) Db::pdo()->lastInsertId();
    }

    public static function markSuccess(int $id): void
    {
        Db::query('UPDATE login_attempts SET success = 1 WHERE id = :id', ['id' => $id]);
    }

    /**
     * Số lần sai trong $minutes phút, tách theo: cùng IP + email (pair), cùng email từ mọi IP (email),
     * cùng IP với mọi email (ip). Auth dùng ngưỡng khác nhau cho từng loại.
     *
     * @return array{pair: int, email: int, ip: int}
     */
    public static function failureCounts(string $ip, string $email, int $minutes): array
    {
        $row = Db::one(
            'SELECT
               COALESCE(SUM(ip = :ip1 AND email = :email1), 0) AS pair,
               COALESCE(SUM(email = :email2), 0) AS by_email,
               COALESCE(SUM(ip = :ip2), 0) AS by_ip
             FROM login_attempts
             WHERE success = 0 AND created_at >= (NOW() - INTERVAL :mins MINUTE)
               AND (ip = :ip3 OR email = :email3)',
            [
                'ip1' => $ip, 'ip2' => $ip, 'ip3' => $ip,
                'email1' => self::normalize($email), 'email2' => self::normalize($email), 'email3' => self::normalize($email),
                'mins' => $minutes,
            ]
        ) ?? [];
        return ['pair' => (int) ($row['pair'] ?? 0), 'email' => (int) ($row['by_email'] ?? 0), 'ip' => (int) ($row['by_ip'] ?? 0)];
    }

    /** Khóa tư vấn MySQL theo email (tuần tự hóa các lần thử). True nếu lấy được khóa trong $timeout giây. */
    public static function lock(string $email, int $timeout = 5): bool
    {
        return (int) Db::value('SELECT GET_LOCK(:name, :t)', ['name' => self::lockName($email), 't' => $timeout]) === 1;
    }

    public static function unlock(string $email): void
    {
        Db::value('SELECT RELEASE_LOCK(:name)', ['name' => self::lockName($email)]);
    }

    private static function lockName(string $email): string
    {
        return 'tt_login_' . sha1(self::normalize($email));
    }

    /** Dọn bản ghi cũ hơn 30 ngày (gọi khi đăng nhập thành công). */
    public static function prune(): void
    {
        Db::query('DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 30 DAY)');
    }

    private static function normalize(string $email): string
    {
        return mb_substr(mb_strtolower(trim($email)), 0, 190);
    }
}
