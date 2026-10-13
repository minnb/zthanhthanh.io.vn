<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use RuntimeException;

/** Lỗi API kèm dữ liệu bổ sung trả cho client (vd lỗi theo từng trường – 422, bài trùng – 409). */
final class ApiException extends RuntimeException
{
    /** @param array<string, mixed> $extra */
    public function __construct(public readonly int $status, string $message, public readonly array $extra = [])
    {
        parent::__construct($message);
    }
}
