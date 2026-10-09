<?php
declare(strict_types=1);

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

    // Trang dev, chỉ có ở môi trường local
    if (Config::isLocal()) {
        $router->get('/_dev/health', [DevController::class, 'health']);
        $router->get('/_dev/ui', [DevController::class, 'ui']);
        $router->get('/_dev/frames', [DevController::class, 'frames']);
        $router->get('/_dev/og', [DevController::class, 'og']);
    }

    // Danh mục bài viết (slug lấy từ bảng categories; danh mục không tồn tại -> 404 trong controller)
    $router->get('/{category}', [PostController::class, 'category']);
    $router->get('/{category}/{slug}', [PostController::class, 'show']);
};
