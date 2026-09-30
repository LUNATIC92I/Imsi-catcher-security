<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database;
final class LabScore extends Model {
    protected static string $table = 'lab_scores';
    public static function award(int $userId, ?int $scenarioId, int $points, string $category, string $detail): int {
        return Database::insert(
            'INSERT INTO lab_scores (user_id, scenario_id, points, category, detail) VALUES (?,?,?,?,?)',
            [$userId, $scenarioId, $points, $category, $detail]
        );
    }
    public static function totalFor(int $userId): int {
        return (int) Database::scalar('SELECT COALESCE(SUM(points),0) FROM lab_scores WHERE user_id = ?', [$userId]);
    }
    public static function leaderboard(int $limit = 20): array {
        return Database::all(
            'SELECT u.username, COALESCE(SUM(s.points),0) AS total
             FROM users u LEFT JOIN lab_scores s ON s.user_id = u.id
             GROUP BY u.id, u.username ORDER BY total DESC LIMIT ?', [max(1,min($limit,100))]
        );
    }
}
