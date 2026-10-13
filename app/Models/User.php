<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;

final class User
{
    public const ROLES = ['editor', 'admin'];

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Db::one('SELECT id, email, name, role, password_hash, last_login_at FROM users WHERE id = :id', ['id' => $id]);
    }

    /** @return array<string, mixed>|null */
    public static function findByEmail(string $email): ?array
    {
        return Db::one(
            'SELECT id, email, name, role, password_hash FROM users WHERE email = :email',
            ['email' => mb_strtolower(trim($email))]
        );
    }

    public static function create(string $email, string $name, string $password, string $role): int
    {
        Db::query(
            'INSERT INTO users (email, name, password_hash, role) VALUES (:email, :name, :hash, :role)',
            [
                'email' => mb_strtolower(trim($email)),
                'name' => trim($name),
                'hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => in_array($role, self::ROLES, true) ? $role : 'editor',
            ]
        );
        return (int) Db::pdo()->lastInsertId();
    }

    /** Đổi mật khẩu và thu hồi mọi token "ghi nhớ" (cookie bị lộ không còn dùng được). */
    public static function setPassword(int $id, string $password): void
    {
        Db::query('UPDATE users SET password_hash = :hash WHERE id = :id', [
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => $id,
        ]);
        RememberToken::deleteForUser($id);
    }

    public static function touchLogin(int $id): void
    {
        Db::query('UPDATE users SET last_login_at = NOW() WHERE id = :id', ['id' => $id]);
    }

    /** Thuật toán băm mới hơn -> băm lại (password_needs_rehash). */
    public static function rehashIfNeeded(array $user, string $password): void
    {
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            self::setPassword((int) $user['id'], $password);
        }
    }
}
