<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Cache;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\View;
use App\Models\AuditLog;

/**
 * Nền cho mọi controller admin (admin.md):
 * - guard(): bắt đăng nhập + đúng quyền, và kiểm CSRF cho MỌI request POST.
 * - Sau thao tác ghi: Cache::flush() + audit_log.
 */
abstract class AdminController
{
    /** @var array<string, mixed> */
    protected array $user = [];

    /** @return array<string, mixed> */
    protected function guard(string $role = 'editor'): array
    {
        $this->user = Auth::require($role);
        if (Request::isPost()) {
            Csrf::verify();
        }
        return $this->user;
    }

    /** @param array<string, mixed> $data */
    protected function render(string $template, array $data = []): string
    {
        return View::render('admin/' . $template, $data + [
            'user' => $this->user,
            'flash' => Flash::pull(),
            'csrf' => Csrf::token(),
            'noindex' => true,
        ], 'layouts/admin');
    }

    /** Chuyển hướng 303 sau POST (Post/Redirect/Get), kèm toast tùy chọn. */
    protected function redirect(string $path, string $message = '', string $type = 'success'): never
    {
        if ($message !== '') {
            Flash::add($type, $message);
        }
        throw new HttpException(303, '', ['Location' => url($path)]);
    }

    /** @param array<string, mixed> $data */
    protected function json(array $data, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Ghi nhật ký + xóa cache trang công khai (mọi thay đổi nội dung đều có thể hiện ra ngoài). */
    protected function changed(string $action, string $entityType, ?int $entityId, array $details = []): void
    {
        AuditLog::record(isset($this->user['id']) ? (int) $this->user['id'] : null, $action, $entityType, $entityId, $details, Request::ip());
        Cache::flush();
    }

    protected function notFound(): never
    {
        throw HttpException::notFound();
    }
}
