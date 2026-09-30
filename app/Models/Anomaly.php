<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class Anomaly extends Model {
    protected static string $table = 'anomalies';
    public static function log(array $d): int {
        return Database::insert(
            'INSERT INTO anomalies (event_id, device_id, cell_id, anomaly_type, risk_level, score, reason)
             VALUES (?,?,?,?,?,?,?)',
            [
                $d['event_id'] ?? null, $d['device_id'] ?? null, $d['cell_id'] ?? null,
                $d['anomaly_type'], $d['risk_level'], $d['score'] ?? 0, $d['reason'],
            ]
        );
    }
    public static function recent(int $limit = 100): array {
        return Database::all('SELECT * FROM anomalies ORDER BY id DESC LIMIT ?', [max(1,min($limit,1000))]);
    }
}
