<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\ErrorHandler;
use App\Models\AdminPost;

final class DashboardController extends AdminController
{
    public function index(): string
    {
        $this->guard('editor');
        // Dọn thùng rác quá 30 ngày (nhẹ, chạy khi vào Tổng quan thay cho cron)
        AdminPost::purgeOldTrash();

        return $this->render('dashboard', [
            'title' => 'Tổng quan',
            'counts' => AdminPost::counts(),
            'recent' => AdminPost::recent(5),
            'warnings' => AdminPost::seoWarnings(8),
            'traffic' => self::traffic(),
        ]);
    }

    /** Lượt xem hôm nay / 7 ngày; null nếu chưa có bảng thống kê (chưa chạy migration) – Tổng quan vẫn mở được. */
    public static function traffic(): ?array
    {
        try {
            return StatsController::summary();
        } catch (\PDOException $e) {
            ErrorHandler::log($e);
            return null;
        }
    }

    /** Màn "Thêm" trên điện thoại: các mục không nằm trên thanh tab đáy. */
    public function more(): string
    {
        $this->guard('editor');
        return $this->render('more', ['title' => 'Thêm']);
    }
}
