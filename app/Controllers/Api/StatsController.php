<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class StatsController extends Controller
{
    public function summary(Request $request): void
    {
        $this->requireAuth($request);

        $stats = [
            'devices'        => (int) Database::scalar('SELECT COUNT(*) FROM devices'),
            'cells_active'   => (int) Database::scalar('SELECT COUNT(*) FROM cells'),
            'legit_cells'    => (int) Database::scalar('SELECT COUNT(*) FROM cells WHERE is_legitimate = 1'),
            'rogue_cells'    => (int) Database::scalar('SELECT COUNT(*) FROM cells WHERE is_rogue = 1'),
            'bts'            => (int) Database::scalar('SELECT COUNT(*) FROM bts'),
            'operators'      => (int) Database::scalar('SELECT COUNT(*) FROM operators'),
            'events'         => (int) Database::scalar('SELECT COUNT(*) FROM connection_events'),
            'anomalies'      => (int) Database::scalar('SELECT COUNT(*) FROM anomalies'),
            'alerts_open'    => (int) Database::scalar("SELECT COUNT(*) FROM alerts WHERE status = 'open'"),
            'handovers'      => (int) Database::scalar('SELECT COUNT(*) FROM handovers'),
        ];

        $byTech = Database::all(
            'SELECT technology, COUNT(*) AS n FROM cells GROUP BY technology ORDER BY technology'
        );
        $byRisk = Database::all(
            'SELECT risk_level, COUNT(*) AS n FROM anomalies GROUP BY risk_level'
        );

        $this->json([
            'stats'      => $stats,
            'by_tech'    => $byTech,
            'by_risk'    => $byRisk,
            'simulation' => true,
        ]);
    }

    public function timeseries(Request $request): void
    {
        $this->requireAuth($request);
        // Events per hour for the last 24 buckets (simulated data).
        $rows = Database::all(
            "SELECT DATE_FORMAT(created_at, '%Y-%m-%d %H:00') AS bucket,
                    COUNT(*) AS events,
                    SUM(risk_level IN ('HIGH','CRITICAL')) AS high
             FROM connection_events
             WHERE created_at >= NOW() - INTERVAL 24 HOUR
             GROUP BY bucket ORDER BY bucket"
        );
        $this->json(['series' => $rows, 'simulation' => true]);
    }
}
