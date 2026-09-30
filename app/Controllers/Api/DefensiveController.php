<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Alert;

/**
 * DefensiveController — simulates SOC defensive responses. All actions apply
 * only to virtual lab objects.
 */
final class DefensiveController extends Controller
{
    public function respond(Request $request): void
    {
        $this->requirePermission($request, 'soc.respond');
        $action = (string) $request->input('action', '');
        $alertId = (int) $request->input('alert_id', 0);
        $alert = Alert::find($alertId);
        if ($alert === null) {
            $this->json(['error' => 'Alerte introuvable'], 404);
        }

        $map = [
            'block_cell'    => ['status' => 'contained', 'msg' => 'Cellule suspecte bloquée (simulation)'],
            'notify_soc'    => ['status' => 'investigating', 'msg' => 'Notification SOC envoyée (simulation)'],
            'policy_change' => ['status' => 'investigating', 'msg' => 'Politique de sécurité renforcée (simulation)'],
            'open_incident' => ['status' => 'investigating', 'msg' => 'Incident ouvert (simulation)'],
            'investigate'   => ['status' => 'investigating', 'msg' => 'Investigation en cours (simulation)'],
            'close'         => ['status' => 'closed', 'msg' => 'Incident clôturé (simulation)'],
        ];

        if (!isset($map[$action])) {
            $this->json(['error' => 'Action inconnue', 'valid' => array_keys($map)], 422);
        }

        Alert::setStatus($alertId, $map[$action]['status']);
        $this->audit('defensive_response', ['action' => $action, 'alert_id' => $alertId]);
        $this->json([
            'ok'         => true,
            'action'     => $action,
            'status'     => $map[$action]['status'],
            'message'    => $map[$action]['msg'],
            'simulation' => true,
        ]);
    }
}
