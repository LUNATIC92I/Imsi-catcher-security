<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class Cell extends Model {
    protected static string $table = 'cells';
    public static function withContext(int $limit = 1000): array {
        return Database::all(
            'SELECT c.*, b.code AS bts_code, b.lat, b.lng, o.name AS operator_name, o.mcc, o.mnc
             FROM cells c
             JOIN bts b ON b.id = c.bts_id
             JOIN operators o ON o.id = c.operator_id
             ORDER BY c.id DESC LIMIT ?', [max(1,min($limit,5000))]
        );
    }
    public static function rogues(): array {
        return Database::all(
            'SELECT c.*, b.lat, b.lng FROM cells c JOIN bts b ON b.id=c.bts_id
             WHERE c.is_rogue = 1 ORDER BY c.id DESC'
        );
    }
    public static function find(int $id): ?array {
        return Database::first(
            'SELECT c.*, b.lat, b.lng, o.mcc, o.mnc FROM cells c
             JOIN bts b ON b.id=c.bts_id JOIN operators o ON o.id=c.operator_id
             WHERE c.id = ?', [$id]
        );
    }
}
