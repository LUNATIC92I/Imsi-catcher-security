<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class Bts extends Model {
    protected static string $table = 'bts';
    public static function withOperator(int $limit = 500): array {
        return Database::all(
            'SELECT b.*, o.name AS operator_name, o.code AS operator_code
             FROM bts b JOIN operators o ON o.id = b.operator_id
             ORDER BY b.id DESC LIMIT ?', [max(1,min($limit,5000))]
        );
    }
}
