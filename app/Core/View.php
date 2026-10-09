<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Render template PHP trong theme đang bật: themes/<theme>/<template>.php.
 * Template trang được bọc trong layout (mặc định layouts/base), layout nhận biến $content.
 */
final class View
{
    /** @var array<string, mixed> Dữ liệu dùng chung cho mọi template (thông tin site, menu...) */
    private static array $shared = [];

    /** @param array<string, mixed> $data */
    public static function share(array $data): void
    {
        self::$shared = $data + self::$shared;
    }

    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/base'): string
    {
        $data += self::$shared;
        $content = self::partial($template, $data);
        if ($layout === null) {
            return $content;
        }
        return self::partial($layout, $data + ['content' => $content]);
    }

    /**
     * Render một template không có layout (dùng cho partials, blocks).
     *
     * @param array<string, mixed> $data
     */
    public static function partial(string $template, array $data = []): string
    {
        $data += self::$shared;
        $file = self::path($template);
        if (!is_file($file)) {
            throw new RuntimeException("Không tìm thấy template: {$template}");
        }
        return (static function (string $__file, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            try {
                require $__file;
                return (string) ob_get_clean();
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
        })($file, $data);
    }

    public static function path(string $template): string
    {
        if (!preg_match('#^[a-z0-9/_-]+$#', $template)) {
            throw new RuntimeException("Tên template không hợp lệ: {$template}");
        }
        $theme = (string) Config::get('theme', 'default');
        return APP_ROOT . "/themes/{$theme}/{$template}.php";
    }
}
