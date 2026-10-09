<?php
declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

/**
 * Lỗi PHP -> ErrorException. Exception không bắt được -> ghi log, trả 500.
 * Local: hiển thị chi tiết. Production: chỉ thông báo chung.
 */
final class ErrorHandler
{
    /** Loại lỗi chỉ ghi log ở production thay vì ném exception. */
    private const SOFT_ERRORS = [E_DEPRECATED, E_USER_DEPRECATED, E_NOTICE, E_USER_NOTICE];

    private static bool $debug = false;
    private static string $logDir = '';

    public static function register(bool $debug, string $logDir): void
    {
        self::$debug = $debug;
        self::$logDir = $logDir;

        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            $exception = new ErrorException($message, 0, $severity, $file, $line);
            // Production: deprecated/notice chỉ ghi log, không làm hỏng trang (vd hosting nâng PHP)
            if (!self::$debug && in_array($severity, self::SOFT_ERRORS, true)) {
                self::log($exception);
                return true;
            }
            throw $exception;
        });

        set_exception_handler([self::class, 'handle']);
    }

    public static function handle(Throwable $e): void
    {
        self::log($e);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        if (self::$debug) {
            echo '<pre style="white-space:pre-wrap;font:14px/1.5 monospace;padding:16px">'
                . e(get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString())
                . '</pre>';
            return;
        }
        echo '<!doctype html><meta charset="utf-8"><title>Lỗi máy chủ</title>'
            . '<p style="font-family:sans-serif;padding:24px">Xin lỗi, website đang gặp sự cố. Vui lòng thử lại sau.</p>';
    }

    public static function log(Throwable $e): void
    {
        if (self::$logDir === '') {
            return;
        }
        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0775, true);
        }
        $line = sprintf(
            "[%s] %s: %s in %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        @file_put_contents(self::$logDir . '/app-' . date('Y-m') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
