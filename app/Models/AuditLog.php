<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

final class AuditLog extends Model
{
    protected static string $table = 'audit_logs';

    /**
     * Record an audit entry. NEVER stores real telecom identifiers — only
     * synthetic lab identifiers and action metadata.
     */
    public static function record(string $action, array $meta = [], ?string $scenario = null, string $result = 'ok'): void
    {
        $user = Auth::user();
        Database::insert(
            'INSERT INTO audit_logs (user_id, username, action, scenario, ip_address, result, metadata, simulation)
             VALUES (?,?,?,?,?,?,?,1)',
            [
                $user['id'] ?? null,
                $user['username'] ?? null,
                $action,
                $scenario,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $result,
                json_encode($meta, JSON_UNESCAPED_UNICODE),
            ]
        );
    }

    public static function recent(int $limit = 100): array
    {
        $limit = max(1, min($limit, 1000));
        return Database::all('SELECT * FROM audit_logs ORDER BY id DESC LIMIT ?', [$limit]);
    }
}
