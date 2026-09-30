<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class Device extends Model {
    protected static string $table = 'devices';
    public static function withContext(int $limit = 1000): array {
        return Database::all(
            'SELECT d.*, s.imsi_fake, s.code AS sim_code, c.code AS cell_code, c.technology
             FROM devices d
             LEFT JOIN sims s ON s.id = d.sim_id
             LEFT JOIN cells c ON c.id = d.current_cell_id
             ORDER BY d.id DESC LIMIT ?', [max(1,min($limit,5000))]
        );
    }
    public static function moveToCell(int $deviceId, int $cellId, int $signal): void {
        Database::run(
            'UPDATE devices SET current_cell_id = ?, signal_dbm = ?, connection_state = "connected" WHERE id = ?',
            [$cellId, $signal, $deviceId]
        );
    }
    public static function random(int $n = 1): array {
        return Database::all('SELECT * FROM devices ORDER BY RAND() LIMIT ?', [max(1,$n)]);
    }
}
