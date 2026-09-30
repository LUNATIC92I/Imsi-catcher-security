<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\LabScore;
use App\Services\ScoringService;

final class ScoringController extends Controller
{
    public function award(Request $request): void
    {
        $this->requireAuth($request);
        $objective = (string) $request->input('objective', '');
        $scenarioId = $request->input('scenario_id') !== null
            ? (int) $request->input('scenario_id') : null;
        $svc = new ScoringService();
        $result = $svc->award((int) Auth::id(), $objective, $scenarioId);
        if (($result['awarded'] ?? 0) === 0) {
            $this->json($result, 422);
        }
        $this->audit('score_award', ['objective' => $objective, 'points' => $result['awarded']]);
        $this->json(array_merge(['ok' => true], $result));
    }

    public function me(Request $request): void
    {
        $this->requireAuth($request);
        $this->json([
            'total' => LabScore::totalFor((int) Auth::id()),
            'max'   => array_sum(ScoringService::OBJECTIVES),
            'objectives' => ScoringService::OBJECTIVES,
        ]);
    }

    public function leaderboard(Request $request): void
    {
        $this->requireAuth($request);
        $this->json(['leaderboard' => LabScore::leaderboard(20)]);
    }
}
