<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Alert;
use App\Services\SocExporter;

final class SocController extends Controller
{
    /** Return SIEM-style JSON for all recent alerts (simulation:true). */
    public function export(Request $request): void
    {
        $this->requireAuth($request);
        $rows = Database::all(
            'SELECT a.*, d.code AS device_code, c.code AS cell_code
             FROM alerts a
             LEFT JOIN devices d ON d.id = a.device_id
             LEFT JOIN cells c ON c.id = a.cell_id
             ORDER BY a.id DESC LIMIT 100'
        );
        $exporter = new SocExporter();
        $payloads = array_map([$exporter, 'buildPayload'], $rows);
        $this->audit('soc_export', ['count' => count($payloads)]);
        $this->json(['siem_events' => $payloads, 'simulation' => true]);
    }

    /** Mark an alert as exported to the (simulated) SIEM. */
    public function push(Request $request): void
    {
        $this->requirePermission($request, 'soc.push');
        $id = (int) $request->input('alert_id', 0);
        $alert = Alert::find($id);
        if ($alert === null) {
            $this->json(['error' => 'Alerte introuvable'], 404);
        }
        Database::run('UPDATE alerts SET soc_exported = 1 WHERE id = ?', [$id]);
        $exporter = new SocExporter();
        $this->audit('soc_push', ['alert_id' => $id]);
        $this->json(['ok' => true, 'payload' => $exporter->buildPayload($alert), 'simulation' => true]);
    }
}
