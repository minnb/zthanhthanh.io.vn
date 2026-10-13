<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\View;

final class AuthController
{
    public function form(): string
    {
        if (Auth::user() !== null) {
            throw new HttpException(303, '', ['Location' => url('/admin')]);
        }
        return $this->page('', '', Request::query('next'));
    }

    public function login(): string
    {
        Csrf::verify();
        $email = Request::str('email');
        $next = self::safeNext(Request::str('next'));
        $result = Auth::attempt($email, (string) ($_POST['password'] ?? ''), Request::bool('remember'));
        if (!$result['ok']) {
            http_response_code(422);
            return $this->page($result['error'], $email, $next);
        }
        throw new HttpException(303, '', ['Location' => url($next)]);
    }

    public function logout(): string
    {
        Csrf::verify();
        Auth::logout();
        throw new HttpException(303, '', ['Location' => url('/admin/login')]);
    }

    /** Đăng xuất mọi thiết bị (thu hồi mọi token "ghi nhớ"). */
    public function logoutAll(): string
    {
        Csrf::verify();
        Auth::logoutEverywhere();
        throw new HttpException(303, '', ['Location' => url('/admin/login')]);
    }

    private function page(string $error, string $email, string $next): string
    {
        return View::render('admin/login', [
            'title' => 'Đăng nhập quản trị',
            'noindex' => true,
            'error' => $error,
            'email' => $email,
            'next' => self::safeNext($next),
            'csrf' => Csrf::token(),
        ], 'layouts/admin-auth');
    }

    /** Chỉ cho quay lại đường dẫn trong /admin (chống open redirect – security.md). */
    private static function safeNext(string $next): string
    {
        // \z (không phải $): "$" vẫn khớp trước "\n" cuối chuỗi -> next=/admin%0A lọt qua
        return preg_match('#^/admin(/[a-z0-9/_-]*)?\z#', $next) ? $next : '/admin';
    }
}
