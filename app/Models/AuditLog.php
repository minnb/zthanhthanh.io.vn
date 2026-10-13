<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Db;
use App\Core\ErrorHandler;
use Throwable;

/** Ai làm gì, lúc nào (admin.md). Ghi log lỗi thì bỏ qua, không làm hỏng thao tác chính. */
final class AuditLog
{
    /** @param array<string, mixed> $details */
    public static function record(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, array $details = [], ?string $ip = null): void
    {
        try {
            Db::query(
                'INSERT INTO audit_log (user_id, action, entity_type, entity_id, details, ip)
                 VALUES (:uid, :action, :type, :eid, :details, :ip)',
                [
                    'uid' => $userId,
                    'action' => mb_substr($action, 0, 60),
                    'type' => $entityType,
                    'eid' => $entityId,
                    'details' => $details === [] ? null : json_encode($details, JSON_UNESCAPED_UNICODE),
                    'ip' => $ip,
                ]
            );
        } catch (Throwable $e) {
            ErrorHandler::log($e);
        }
    }
}
