<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\AuditLog;
use App\Models\LoginAttempt;
use App\Models\RememberToken;
use App\Models\User;

/**
 * Đăng nhập admin (security.md): password_hash, regenerate session sau đăng nhập,
 * giới hạn sai 5 lần/15 phút theo IP hoặc email, "ghi nhớ" bằng selector + validator băm.
 */
final class Auth
{
    /** Sai liên tiếp từ cùng IP + cùng email -> khóa (chặn dò mật khẩu thông thường). */
    public const MAX_FAILURES = 5;
    /** Sai theo email từ mọi IP: ngưỡng cao hơn để người lạ không khóa được tài khoản tác giả chỉ bằng 5 lần sai. */
    public const MAX_FAILURES_EMAIL = 20;
    /** Sai từ một IP với mọi email (dò nhiều tài khoản). */
    public const MAX_FAILURES_IP = 30;
    public const LOCK_MINUTES = 15;
    private const REMEMBER_COOKIE = 'tt_remember';
    private const REMEMBER_DAYS = 30;
    /** Bcrypt hợp lệ của một chuỗi ngẫu nhiên (không ai biết) – dùng khi email không tồn tại. */
    private const DUMMY_HASH = '$2y$10$Zei5lxcwgMBJIlcVH84Uv.MTajgUNL8qtoynUO5GpJRlDG7Kjvq.C';
    /** Cấp quyền: số càng lớn quyền càng cao. */
    private const LEVEL = ['editor' => 1, 'admin' => 2];

    /** @var array<string, mixed>|null|false false = chưa tra */
    private static array|null|false $user = false;

    /** Người dùng hiện tại (session, hoặc cookie "ghi nhớ"). @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$user !== false) {
            return self::$user;
        }
        self::$user = null;
        $id = Session::get('uid');
        if (is_int($id) && ($user = User::find($id)) !== null) {
            return self::$user = $user;
        }
        if (is_int($id)) {
            Session::forget('uid'); // tài khoản đã bị xóa
        }
        return self::$user = self::loginFromRememberCookie();
    }

    /**
     * Kiểm tra quyền; chưa đăng nhập -> 303 về trang đăng nhập; thiếu quyền -> 403.
     *
     * @return array<string, mixed>
     */
    public static function require(string $role = 'editor'): array
    {
        $user = self::user();
        if ($user === null) {
            $next = Router::currentPath();
            throw new HttpException(303, '', ['Location' => url('/admin/login') . '?next=' . rawurlencode($next)]);
        }
        if (!self::can($user, $role)) {
            throw new HttpException(403, 'Tài khoản của bạn không có quyền vào mục này.');
        }
        return $user;
    }

    /** @param array<string, mixed> $user */
    public static function can(array $user, string $role): bool
    {
        return (self::LEVEL[(string) $user['role']] ?? 0) >= (self::LEVEL[$role] ?? PHP_INT_MAX);
    }

    /**
     * Đăng nhập bằng email + mật khẩu.
     *
     * @return array{ok: bool, error: string}
     */
    public static function attempt(string $email, string $password, bool $remember): array
    {
        $ip = Request::ip();
        $email = mb_strtolower(trim($email));
        if ($email === '' || $password === '') {
            return ['ok' => false, 'error' => 'Nhập email và mật khẩu.'];
        }

        // Tuần tự hóa các lần thử cùng email: request song song không thể cùng lọt qua bước kiểm giới hạn
        if (!LoginAttempt::lock($email)) {
            return ['ok' => false, 'error' => 'Máy chủ đang bận, hãy thử lại sau ít giây.'];
        }
        try {
            $fails = LoginAttempt::failureCounts($ip, $email, self::LOCK_MINUTES);
            if ($fails['pair'] >= self::MAX_FAILURES || $fails['email'] >= self::MAX_FAILURES_EMAIL || $fails['ip'] >= self::MAX_FAILURES_IP) {
                return ['ok' => false, 'error' => 'Bạn đã nhập sai quá nhiều lần. Hãy thử lại sau ' . self::LOCK_MINUTES . ' phút.'];
            }
            // Ghi lần thử (coi là sai) TRƯỚC khi kiểm mật khẩu; đúng thì đánh dấu thành công
            $attemptId = LoginAttempt::record($ip, $email);

            $user = User::findByEmail($email);
            // Luôn chạy password_verify (kể cả khi không có user) để thời gian phản hồi không lộ email có tồn tại
            $hash = $user !== null ? (string) $user['password_hash'] : self::DUMMY_HASH;
            $valid = password_verify($password, $hash) && $user !== null;
            if ($valid) {
                LoginAttempt::markSuccess($attemptId);
            }
        } finally {
            LoginAttempt::unlock($email);
        }

        if (!$valid) {
            AuditLog::record(null, 'login_failed', 'user', null, ['email' => $email], $ip);
            return ['ok' => false, 'error' => 'Email hoặc mật khẩu không đúng.'];
        }

        User::rehashIfNeeded($user, $password);
        self::login($user, $remember);
        LoginAttempt::prune();
        AuditLog::record((int) $user['id'], 'login', 'user', (int) $user['id'], [], $ip);
        return ['ok' => true, 'error' => ''];
    }

    public static function logout(): void
    {
        $user = self::user();
        $cookie = (string) ($_COOKIE[self::REMEMBER_COOKIE] ?? '');
        if (str_contains($cookie, ':')) {
            RememberToken::delete(explode(':', $cookie, 2)[0]);
        }
        self::setRememberCookie('', time() - 3600);
        if ($user !== null) {
            AuditLog::record((int) $user['id'], 'logout', 'user', (int) $user['id'], [], Request::ip());
        }
        Session::destroy();
        self::$user = null;
    }

    /** Đăng xuất mọi thiết bị: thu hồi mọi token "ghi nhớ" của user rồi đăng xuất phiên hiện tại. */
    public static function logoutEverywhere(): void
    {
        $user = self::user();
        if ($user !== null) {
            RememberToken::deleteForUser((int) $user['id']);
            AuditLog::record((int) $user['id'], 'logout_all', 'user', (int) $user['id'], [], Request::ip());
        }
        self::logout();
    }

    /** @param array<string, mixed> $user */
    private static function login(array $user, bool $remember): void
    {
        Session::regenerate(); // chống session fixation
        Session::forget('_csrf'); // token CSRF mới cho phiên đã đăng nhập
        Session::set('uid', (int) $user['id']);
        User::touchLogin((int) $user['id']);
        if ($remember) {
            $token = RememberToken::issue((int) $user['id'], self::REMEMBER_DAYS);
            self::setRememberCookie($token['selector'] . ':' . $token['validator'], $token['expires']);
        }
        self::$user = $user;
    }

    /** @return array<string, mixed>|null */
    private static function loginFromRememberCookie(): ?array
    {
        $cookie = (string) ($_COOKIE[self::REMEMBER_COOKIE] ?? '');
        if (!str_contains($cookie, ':')) {
            return null;
        }
        [$selector, $validator] = explode(':', $cookie, 2);
        $userId = RememberToken::validate($selector, $validator);
        // Xoay vòng: token cũ chỉ còn hiệu lực 30 giây (đủ cho request song song), sau đó vô hiệu
        RememberToken::retire($selector, 30);
        $user = $userId !== null ? User::find($userId) : null;
        if ($user === null) {
            self::setRememberCookie('', time() - 3600);
            return null;
        }
        self::login($user, true); // cấp token mới (xoay vòng)
        RememberToken::pruneExpired();
        return $user;
    }

    private static function setRememberCookie(string $value, int $expires): void
    {
        if (headers_sent()) {
            return;
        }
        setcookie(self::REMEMBER_COOKIE, $value, [
            'expires' => $expires,
            'path' => (Config::basePath() ?: '') . '/admin',
            'secure' => str_starts_with((string) Config::get('base_url'), 'https://'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
