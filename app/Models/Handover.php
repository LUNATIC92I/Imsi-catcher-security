<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class Handover extends Model {
    protected static string $table = 'handovers';
    public static function log(int $deviceId, ?int $from, ?int $to, string $reason): int {
        return Database::insert(
            'INSERT INTO handovers (device_id, from_cell_id, to_cell_id, reason) VALUES (?,?,?,?)',
            [$deviceId, $from, $to, $reason]
        );
    }
}
