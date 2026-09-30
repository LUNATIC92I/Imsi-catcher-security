<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class ConnectionEvent extends Model {
    protected static string $table = 'connection_events';
    public static function log(array $d): int {
        return Database::insert(
            'INSERT INTO connection_events
             (device_id, cell_id, sim_id, event_type, technology, signal_dbm, risk_level, detail)
             VALUES (?,?,?,?,?,?,?,?)',
            [
                $d['device_id'], $d['cell_id'] ?? null, $d['sim_id'] ?? null,
                $d['event_type'], $d['technology'] ?? null, $d['signal_dbm'] ?? null,
                $d['risk_level'] ?? 'LOW', $d['detail'] ?? null,
            ]
        );
    }
    public static function recent(int $limit = 100): array {
        return Database::all(
            'SELECT e.*, d.code AS device_code, c.code AS cell_code
             FROM connection_events e
             JOIN devices d ON d.id = e.device_id
             LEFT JOIN cells c ON c.id = e.cell_id
             ORDER BY e.id DESC LIMIT ?', [max(1,min($limit,1000))]
        );
    }
}
