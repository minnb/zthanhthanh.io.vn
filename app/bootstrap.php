<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\ErrorHandler;

/*
 * Khởi động ứng dụng: autoload, cấu hình, múi giờ, xử lý lỗi.
 * APP_ROOT phải được định nghĩa trước (trong public/index.php).
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Autoload PSR-4 tối giản: App\Foo\Bar -> app/Foo/Bar.php
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_ROOT . '/app/helpers.php';

$configFile = APP_ROOT . '/app/config.php';
if (!is_file($configFile)) {
    // Front controller bắt lỗi này và trả 500 (không exit rải rác – php-app.md)
    throw new RuntimeException('Thiếu app/config.php. Hãy copy từ app/config.sample.php.');
}
Config::load(require $configFile);

date_default_timezone_set((string) Config::get('timezone', 'UTC'));
mb_internal_encoding('UTF-8');

ErrorHandler::register(Config::isLocal(), APP_ROOT . '/storage/logs');
