<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
final class TrainingController extends Controller {
    public function submitQuiz(Request $request): void {
        $this->requireAuth($request);
        $module = (string) $request->input('module', 'general');
        $score  = max(0, (int) $request->input('score', 0));
        $total  = max(0, (int) $request->input('total', 0));
        Database::insert(
            'INSERT INTO quiz_results (user_id, module, score, total) VALUES (?,?,?,?)',
            [(int) Auth::id(), substr($module,0,60), $score, $total]
        );
        $this->audit('quiz_submit', ['module' => $module, 'score' => $score, 'total' => $total]);
        $this->json(['ok' => true, 'module' => $module, 'score' => $score, 'total' => $total]);
    }
    public function myResults(Request $request): void {
        $this->requireAuth($request);
        $rows = Database::all(
            'SELECT module, MAX(score) AS best, MAX(total) AS total FROM quiz_results
             WHERE user_id = ? GROUP BY module', [(int) Auth::id()]
        );
        $this->json(['data' => $rows]);
    }
}
