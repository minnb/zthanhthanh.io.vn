<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/** Ném ra để dừng xử lý và trả về một mã HTTP lỗi (404, 403, 503...). */
final class HttpException extends RuntimeException
{
    /** @param array<string, string> $headers Header gửi kèm, ví dụ ['Allow' => 'GET'] */
    public function __construct(
        public readonly int $status,
        string $message = '',
        public readonly array $headers = [],
    ) {
        parent::__construct($message !== '' ? $message : (string) $status, $status);
    }

    public static function notFound(): self
    {
        return new self(404, 'Không tìm thấy trang');
    }
}
