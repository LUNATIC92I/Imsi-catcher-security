<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\LabScore;

/**
 * ScoringService — awards points for completed lab objectives.
 * Max score per scenario = 100 (5 objectives × 20).
 */
final class ScoringService
{
    public const OBJECTIVES = [
        'detect_rogue_cell'   => 20,
        'identify_downgrade'  => 20,
        'identify_exposure'   => 20,
        'analyze_graph'       => 20,
        'incident_response'   => 20,
    ];

    public function award(int $userId, string $objective, ?int $scenarioId = null): array
    {
        $points = self::OBJECTIVES[$objective] ?? 0;
        if ($points === 0) {
            return ['awarded' => 0, 'error' => 'Objectif inconnu'];
        }
        LabScore::award($userId, $scenarioId, $points, $objective, 'Objectif validé: ' . $objective);
        return [
            'awarded' => $points,
            'total'   => LabScore::totalFor($userId),
            'max'     => array_sum(self::OBJECTIVES),
        ];
    }
}
