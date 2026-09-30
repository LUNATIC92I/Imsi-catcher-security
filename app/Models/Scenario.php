<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class Scenario extends Model {
    protected static string $table = 'scenarios';
    public static function byCode(string $code): ?array {
        return Database::first('SELECT * FROM scenarios WHERE code = ?', [$code]);
    }
    public static function steps(int $scenarioId): array {
        return Database::all(
            'SELECT * FROM scenario_steps WHERE scenario_id = ? ORDER BY step_order ASC',
            [$scenarioId]
        );
    }
}
