<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\HttpException;
use App\Core\Slug;
use App\Core\View;
use App\Models\Film;
use App\Models\SystemInfo;

/** Trang chẩn đoán môi trường, chỉ đăng ký route khi env=local. */
final class DevController
{
    public function health(): string
    {
        if (!Config::isLocal()) {
            throw HttpException::notFound();
        }

        $db = SystemInfo::database();

        $extensions = [];
        // exif: thiếu thì ảnh dọc chụp từ điện thoại bị nằm ngang (Image bỏ qua bước xoay)
        foreach (['pdo_mysql', 'mbstring', 'fileinfo', 'gd', 'exif', 'intl'] as $ext) {
            $extensions[$ext] = extension_loaded($ext);
        }

        return View::render('pages/dev-health', [
            'title' => 'Chẩn đoán hệ thống',
            'php' => PHP_VERSION,
            'extensions' => $extensions,
            // Giới hạn liên quan upload ảnh + bộ nhớ nền của một request web (sau bootstrap, session, DB)
            'limits' => [
                'memory_limit' => (string) ini_get('memory_limit'),
                'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
                'post_max_size' => (string) ini_get('post_max_size'),
                'Bộ nhớ nền request web' => sprintf('%.1f MB', memory_get_usage(true) / 1048576),
            ],
            'db' => $db,
            'baseUrl' => (string) Config::get('base_url'),
            'slugSample' => Slug::make('Phim ngắn ĐỢI: Không để người khác quyết định giới hạn của bản thân!'),
        ], 'layouts/dev');
    }

    /**
     * Khung xem nhiều trang ở 375px bằng iframe cùng origin (X-Frame-Options: SAMEORIGIN
     * chặn trang khung mở từ file://). Dùng bởi skill verify-site: /_dev/frames?p=/,/phim/doi
     */
    public function frames(): string
    {
        if (!Config::isLocal()) {
            throw HttpException::notFound();
        }
        $paths = array_slice(array_filter(
            array_map('trim', explode(',', (string) ($_GET['p'] ?? '/'))),
            static fn (string $p): bool => (bool) preg_match('#^/[a-z0-9/_-]*$#', $p)
        ), 0, 4);

        $html = '<!doctype html><meta charset="utf-8"><title>frames</title>'
            . '<body style="margin:0;display:flex;gap:12px;background:#666;padding:8px">';
        foreach ($paths as $p) {
            $html .= '<figure style="margin:0"><figcaption style="font:12px sans-serif;color:#fff">' . e($p) . '</figcaption>'
                . '<iframe src="' . e(url($p)) . '" style="width:375px;height:1600px;border:0;background:#fff"></iframe></figure>';
        }
        return $html . '</body>';
    }

    /**
     * Khung 1200×630 để chụp ảnh chia sẻ mặc định (og-default.png) bằng Edge headless.
     * Ảnh sinh ra lưu ở themes/default/assets/img/, build_assets copy sang public/assets/img/.
     */
    public function og(): string
    {
        if (!Config::isLocal()) {
            throw HttpException::notFound();
        }
        $film = Film::featured();
        return View::render('pages/dev-og', [
            'title' => 'OG',
            'noindex' => true,
            'film' => $film,
        ], null);
    }

    /**
     * Xem trước giao diện admin để chụp màn hình (chỉ local). KHÔNG đăng nhập ai: chỉ render template
     * với người dùng giả; mọi form vẫn gửi tới endpoint thật, nơi bắt đăng nhập + CSRF như thường.
     */
    public function admin(string $screen): string
    {
        if (!Config::isLocal()) {
            throw HttpException::notFound();
        }
        $base = ['user' => ['id' => 0, 'name' => 'Xem thử giao diện', 'role' => 'admin'], 'flash' => [], 'csrf' => 'dev-preview', 'noindex' => true];
        $firstPost = (int) (\App\Core\Db::value('SELECT id FROM posts WHERE deleted_at IS NULL ORDER BY id LIMIT 1') ?? 0);
        $data = match ($screen) {
            'dashboard' => ['template' => 'dashboard', 'title' => 'Tổng quan', 'counts' => \App\Models\AdminPost::counts(),
                'recent' => \App\Models\AdminPost::recent(5), 'warnings' => \App\Models\AdminPost::seoWarnings(8)],
            'posts' => ['template' => 'posts/index', 'title' => 'Bài viết',
                'filters' => ['q' => '', 'status' => '', 'category' => 0, 'source' => '', 'trash' => false],
                'result' => \App\Models\AdminPost::paginate([], 1), 'page' => 1,
                'categories' => \App\Models\Category::all(), 'counts' => \App\Models\AdminPost::counts()],
            'edit' => ['template' => 'posts/edit', 'title' => 'Sửa bài', 'post' => \App\Models\AdminPost::find($firstPost) ?? throw HttpException::notFound(),
                'errors' => [], 'categories' => \App\Models\Category::all(), 'statuses' => \App\Models\AdminPost::STATUSES, 'publicUrl' => null],
            'media' => ['template' => 'media/index', 'title' => 'Thư viện ảnh', 'result' => (static function (): array { $r = \App\Models\Media::paginate(1); $r['items'] = \App\Controllers\Admin\MediaController::libraryItems($r['items']); return $r; })(), 'page' => 1, 'pages' => 1, 'q' => ''],
            'film' => ['template' => 'films/edit', 'title' => 'Phim', 'film' => \App\Models\AdminFilm::find(1) ?? throw HttpException::notFound(),
                'errors' => [], 'statuses' => \App\Models\AdminFilm::STATUSES, 'milestones' => \App\Models\AdminFilm::milestones(1)],
            'settings' => (static function (): array {
                $values = [];
                foreach (array_keys(\App\Controllers\Admin\SettingsController::FIELDS) as $key) {
                    $values[$key] = (string) \App\Models\Setting::get($key, '');
                }
                return ['template' => 'settings', 'title' => 'Cài đặt', 'fields' => \App\Controllers\Admin\SettingsController::FIELDS,
                    'values' => $values, 'errors' => [], 'previews' => \App\Controllers\Admin\SettingsController::previews($values)];
            })(),
            'more' => ['template' => 'more', 'title' => 'Thêm'],
            default => throw HttpException::notFound(),
        };
        $template = $data['template'];
        unset($data['template']);
        return View::render('admin/' . $template, $data + $base, 'layouts/admin');
    }

    /** Thư viện giao diện: xem trước các partial với dữ liệu mẫu. */
    public function ui(): string
    {
        if (!Config::isLocal()) {
            throw HttpException::notFound();
        }
        $film = Film::featured();

        return View::render('pages/dev-ui', [
            'title' => 'Thư viện giao diện',
            'milestones' => $film !== null ? Film::milestones((int) $film['id']) : [],
            'samplePosts' => [
                ['title' => 'Không để người khác quyết định giới hạn của bản thân', 'url' => '/hau-truong-bien-kich/vi-du-1',
                 'excerpt' => '“Bà mà đóng vai chính, phim của bà bể là cái chắc.” Câu nói ấy theo mình suốt hai ngày ra trường quay…',
                 'category' => 'Hậu trường', 'reading_minutes' => 4],
                ['title' => 'Đứng giữa chợ rau rao bán sách Account: bài học về khán giả mục tiêu', 'url' => '/goc-nghe/vi-du-2',
                 'excerpt' => 'Trước khi viết, câu đầu tiên phải trả lời: mình kể câu chuyện này cho ai xem?',
                 'category' => 'Góc nghề', 'reading_minutes' => 3],
                ['title' => 'Giấy phép phân loại phim: chiếc vé thông hành ai làm phim cũng cần', 'url' => '/goc-nghe/vi-du-3',
                 'excerpt' => 'Nộp online, khoảng 10 ngày, lệ phí 1,6 triệu cho phim dưới 60 phút. Kinh nghiệm thật từ ĐỢI.',
                 'category' => 'Góc nghề', 'reading_minutes' => 5],
            ],
        ], 'layouts/dev');
    }
}
