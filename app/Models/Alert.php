<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class Alert extends Model {
    protected static string $table = 'alerts';
    public static function log(array $d): int {
        return Database::insert(
            'INSERT INTO alerts (anomaly_id, device_id, cell_id, event_type, severity, status, message, simulation)
             VALUES (?,?,?,?,?,?,?,1)',
            [
                $d['anomaly_id'] ?? null, $d['device_id'] ?? null, $d['cell_id'] ?? null,
                $d['event_type'], $d['severity'], $d['status'] ?? 'open', $d['message'],
            ]
        );
    }
    public static function setStatus(int $id, string $status): void {
        Database::run('UPDATE alerts SET status = ? WHERE id = ?', [$status, $id]);
    }
    public static function recent(int $limit = 100): array {
        return Database::all('SELECT * FROM alerts ORDER BY id DESC LIMIT ?', [max(1,min($limit,1000))]);
    }
}
