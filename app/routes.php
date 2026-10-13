<?php
declare(strict_types=1);

use App\Controllers\Admin;
use App\Controllers\DevController;
use App\Controllers\FilmController;
use App\Controllers\HomeController;
use App\Controllers\PageController;
use App\Controllers\PostController;
use App\Controllers\SeoController;
use App\Core\Config;
use App\Core\Router;

/**
 * Khai báo route. Router khớp theo thứ tự khai báo: route cụ thể phải đứng TRƯỚC
 * hai route chung /{category} và /{category}/{slug} ở cuối file.
 */
return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);

    $router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
    $router->get('/robots.txt', [SeoController::class, 'robots']);
    $router->get('/feed.xml', [SeoController::class, 'feed']);

    $router->get('/phim', [FilmController::class, 'index']);
    $router->get('/phim/{slug}', [FilmController::class, 'show']);

    $router->get('/chu-de/{tag}', [PostController::class, 'tag']);

    $router->get('/gioi-thieu', [PageController::class, 'about']);
    $router->get('/lien-he', [PageController::class, 'contact']);
    $router->get('/en/press-kit', [PageController::class, 'pressKit']);

    // ---------------- Admin (mọi action tự kiểm quyền + CSRF trong AdminController::guard) ----------------
    $router->get('/admin/login', [Admin\AuthController::class, 'form']);
    $router->post('/admin/login', [Admin\AuthController::class, 'login']);
    $router->post('/admin/logout', [Admin\AuthController::class, 'logout']);
    $router->post('/admin/logout-all', [Admin\AuthController::class, 'logoutAll']);

    $router->get('/admin', [Admin\DashboardController::class, 'index']);
    $router->get('/admin/them', [Admin\DashboardController::class, 'more']);

    $router->get('/admin/posts', [Admin\PostController::class, 'index']);
    $router->post('/admin/posts/new', [Admin\PostController::class, 'create']);       // trước /{id}
    $router->get('/admin/posts/{id}', [Admin\PostController::class, 'edit']);
    $router->post('/admin/posts/{id}', [Admin\PostController::class, 'update']);
    $router->post('/admin/posts/{id}/trash', [Admin\PostController::class, 'trash']);
    $router->post('/admin/posts/{id}/restore', [Admin\PostController::class, 'restore']);

    $router->get('/admin/media', [Admin\MediaController::class, 'index']);
    $router->post('/admin/media/upload', [Admin\MediaController::class, 'upload']);   // trước /{id}
    $router->post('/admin/media/{id}', [Admin\MediaController::class, 'update']);
    $router->post('/admin/media/{id}/delete', [Admin\MediaController::class, 'delete']);

    $router->get('/admin/films', [Admin\FilmController::class, 'index']);
    $router->get('/admin/films/{id}', [Admin\FilmController::class, 'edit']);
    $router->post('/admin/films/{id}', [Admin\FilmController::class, 'update']);
    $router->post('/admin/films/{id}/milestones', [Admin\FilmController::class, 'addMilestone']);
    $router->post('/admin/milestones/{id}', [Admin\FilmController::class, 'updateMilestone']);
    $router->post('/admin/milestones/{id}/delete', [Admin\FilmController::class, 'deleteMilestone']);
    $router->post('/admin/milestones/{id}/move', [Admin\FilmController::class, 'moveMilestone']);

    $router->get('/admin/pages', [Admin\PageController::class, 'index']);
    $router->get('/admin/pages/{id}', [Admin\PageController::class, 'edit']);
    $router->post('/admin/pages/{id}', [Admin\PageController::class, 'update']);

    $router->get('/admin/settings', [Admin\SettingsController::class, 'edit']);
    $router->post('/admin/settings', [Admin\SettingsController::class, 'update']);
    $router->post('/admin/settings/cache', [Admin\SettingsController::class, 'flushCache']);

    $router->post('/admin/api/preview', [Admin\ApiController::class, 'preview']);
    $router->post('/admin/api/posts/{id}/autosave', [Admin\ApiController::class, 'autosave']);
    $router->get('/admin/api/media', [Admin\ApiController::class, 'media']);
    // Tool đăng bài (tools/contents/fbpost): phiên + tạo/sửa bài qua JSON
    $router->get('/admin/api/session', [Admin\ApiController::class, 'session']);
    $router->post('/admin/api/posts', [Admin\ApiController::class, 'createPost']);
    $router->post('/admin/api/posts/{id}', [Admin\ApiController::class, 'updatePost']);

    // Trang dev, chỉ có ở môi trường local
    if (Config::isLocal()) {
        $router->get('/_dev/health', [DevController::class, 'health']);
        $router->get('/_dev/ui', [DevController::class, 'ui']);
        $router->get('/_dev/frames', [DevController::class, 'frames']);
        $router->get('/_dev/og', [DevController::class, 'og']);
        $router->get('/_dev/admin/{screen}', [DevController::class, 'admin']);
    }

    // Danh mục bài viết (slug lấy từ bảng categories; danh mục không tồn tại -> 404 trong controller)
    $router->get('/{category}', [PostController::class, 'category']);
    $router->get('/{category}/{slug}', [PostController::class, 'show']);
};
