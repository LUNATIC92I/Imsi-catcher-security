<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Alert;
use App\Models\Anomaly;
use App\Models\Cell;
use App\Models\ConnectionEvent;
use App\Models\Device;

/**
 * ScenarioRunner — executes a pedagogical attack scenario end-to-end using
 * only virtual lab objects, and returns a timeline suitable for the
 * "Attack Simulation Replay" front-end.
 */
final class ScenarioRunner
{
    private DetectionEngine $engine;

    public function __construct()
    {
        $this->engine = new DetectionEngine();
    }

    /**
     * Run a scenario by category and return an ordered timeline of steps.
     * Persists a real (simulated) event → anomaly → alert chain so the
     * dashboard reflects the run.
     */
    public function run(string $category): array
    {
        $device = Device::random(1)[0] ?? null;
        if ($device === null) {
            throw new \RuntimeException('Aucun appareil virtuel disponible. Lancez le seed.');
        }

        $legitCell = Cell::withContext(1)[0] ?? null;
        $timeline = [];

        $timeline[] = $this->step('phone', 'Téléphone virtuel', $device['code'],
            "Appareil {$device['code']} en veille sur le réseau simulé.");

        if ($legitCell) {
            $timeline[] = $this->step('legit_cell', 'Cellule légitime', $legitCell['code'],
                "Rattachement normal ({$legitCell['technology']}), signal nominal.");
        }

        // Build the scenario-specific detection context.
        $rogue = null;
        $ctx = ['previous_technology' => $legitCell['technology'] ?? '4G'];

        switch ($category) {
            case 'downgrade':
                $rogue = $this->makeVirtualRogue('2G', -48);
                $ctx['identity_requested'] = true;
                $timeline[] = $this->step('rogue_cell', 'Cellule 2G suspecte', $rogue['code'],
                    'Une cellule 2G à forte puissance apparaît et force le downgrade.');
                break;
            case 'identity_exposure':
                $rogue = $this->makeVirtualRogue('4G', -46);
                $ctx['identity_requested'] = true;
                $timeline[] = $this->step('rogue_cell', 'Cellule inconnue', $rogue['code'],
                    "La cellule demande explicitement l'identité (IMSI simulé).");
                break;
            case 'cell_spoofing':
                $rogue = $this->makeVirtualRogue('4G', -55);
                $ctx['inconsistent_cell_id'] = true;
                $ctx['expected_mcc'] = $legitCell['mcc'] ?? '001';
                $rogue['mcc'] = '999';
                $timeline[] = $this->step('rogue_cell', 'Cellule usurpée', $rogue['code'],
                    'Caractéristiques incohérentes (Cell ID / MCC anormaux).');
                break;
            case 'location_tracking':
                $rogue = $this->makeVirtualRogue('4G', -50);
                $ctx['sudden_cell_change'] = true;
                $timeline[] = $this->step('tracking', 'Suivi simulé', $device['code'],
                    'Trajectoire virtuelle générée aléatoirement (aucune position réelle).');
                break;
            case 'rogue_cell':
            default:
                $rogue = $this->makeVirtualRogue('2G', -44);
                $ctx['identity_requested'] = true;
                $ctx['sudden_cell_change'] = true;
                $ctx['inconsistent_cell_id'] = true;
                $timeline[] = $this->step('rogue_cell', 'Rogue base station', $rogue['code'],
                    'Fausse station à proximité de la cellule légitime.');
                break;
        }

        // Step: device changes cell
        $timeline[] = $this->step('handover', 'Changement de cellule', $rogue['code'],
            "{$device['code']} bascule vers {$rogue['code']}.");

        // Persist a simulated connection event
        $eventId = ConnectionEvent::log([
            'device_id'  => (int) $device['id'],
            'cell_id'    => null,
            'sim_id'     => $device['sim_id'] ?? null,
            'event_type' => $category === 'downgrade' ? 'downgrade' : 'attach',
            'technology' => $rogue['technology'],
            'signal_dbm' => $rogue['power_dbm'],
            'risk_level' => 'HIGH',
            'detail'     => 'Événement de scénario simulé: ' . $category,
        ]);

        // Detection
        $analysis = $this->engine->analyze($rogue, $ctx);
        $timeline[] = $this->step('exposure', 'Exposition détectée', $device['code'],
            'Un identifiant mobile simulé est exposé à la cellule suspecte.');
        $timeline[] = $this->step('detection', 'Détection', $rogue['code'],
            "Moteur de détection: {$analysis['risk_level']} (score {$analysis['score']}).");

        // Persist anomaly + alert
        $anomalyId = Anomaly::log([
            'event_id'     => $eventId,
            'device_id'    => (int) $device['id'],
            'cell_id'      => null,
            'anomaly_type' => $analysis['anomaly_type'],
            'risk_level'   => $analysis['risk_level'],
            'score'        => $analysis['score'],
            'reason'       => $analysis['findings'][0]['reason'] ?? 'Comportement anormal simulé',
        ]);

        // The scenario category is what the exercise is teaching, so it drives
        // the alert label; detection drives the severity.
        $categoryEventMap = [
            'downgrade'         => 'DOWNGRADE_ATTACK_DETECTED',
            'identity_exposure' => 'IMSI_EXPOSURE_DETECTED',
            'cell_spoofing'     => 'CELL_SPOOFING_DETECTED',
            'location_tracking' => 'LOCATION_TRACKING_DETECTED',
            'rogue_cell'        => 'ROGUE_CELL_DETECTED',
        ];
        $alertId = Alert::log([
            'anomaly_id' => $anomalyId,
            'device_id'  => (int) $device['id'],
            'cell_id'    => null,
            'event_type' => $categoryEventMap[$category] ?? 'ROGUE_CELL_DETECTED',
            'severity'   => $analysis['risk_level'],
            'message'    => "Scénario {$category}: menace simulée détectée sur {$device['code']}.",
        ]);

        $timeline[] = $this->step('alert', 'Alerte générée', 'ALERT-' . $alertId,
            "Alerte {$analysis['risk_level']} créée et transmise au SOC (simulation).");
        $timeline[] = $this->step('response', 'Réponse défensive', $rogue['code'],
            'Cellule suspecte bloquée, incident ouvert, investigation lancée.');

        return [
            'category'    => $category,
            'device'      => $device['code'],
            'analysis'    => $analysis,
            'alert_id'    => $alertId,
            'anomaly_id'  => $anomalyId,
            'timeline'    => $timeline,
            'simulation'  => true,
        ];
    }

    private function makeVirtualRogue(string $tech, int $power): array
    {
        return [
            'code'          => 'CELL-ROGUE-' . strtoupper(bin2hex(random_bytes(2))),
            'technology'    => $tech,
            'power_dbm'     => $power,
            'cell_id_num'   => random_int(60000, 65000),
            'lac'           => random_int(1, 3),
            'is_rogue'      => 1,
            'is_legitimate' => 0,
            'mcc'           => '001',
            'mnc'           => '01',
        ];
    }

    private function step(string $type, string $title, string $ref, string $desc): array
    {
        return [
            'type'        => $type,
            'title'       => $title,
            'ref'         => $ref,
            'description' => $desc,
            'ts'          => gmdate('c'),
        ];
    }
}
