<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Cell;
use App\Models\ConnectionEvent;
use App\Models\Device;

/**
 * RogueCellSimulator — spins up a SIMULATED rogue base station and makes
 * nearby VIRTUAL devices attach to it. It only ever touches lab records.
 */
final class RogueCellSimulator
{
    private DetectionEngine $engine;

    public function __construct()
    {
        $this->engine = new DetectionEngine();
    }

    /** Create (or reuse) a simulated rogue cell attached to a lab BTS. */
    public function spawn(?string $operatorMcc = null): array
    {
        // Pick an arbitrary lab BTS to host the rogue cell (visual anchor).
        $bts = Database::first('SELECT * FROM bts ORDER BY ' . Database::randExpr() . ' LIMIT 1');
        if ($bts === null) {
            throw new \RuntimeException('Aucune BTS de laboratoire disponible.');
        }
        $operator = Database::first('SELECT * FROM operators ORDER BY ' . Database::randExpr() . ' LIMIT 1');

        $suffix = strtoupper(bin2hex(random_bytes(2)));
        $code = 'CELL-ROGUE-' . $suffix;
        $id = Database::insert(
            'INSERT INTO cells
             (code, bts_id, operator_id, cell_id_num, lac, technology, frequency_mhz, power_dbm, is_legitimate, is_rogue, is_simulated)
             VALUES (?,?,?,?,?,?,?,?,0,1,1)',
            [
                $code,
                (int) $bts['id'],
                (int) $operator['id'],
                random_int(60000, 65000),      // implausible cell id
                random_int(1, 5),              // inconsistent LAC
                '2G',                          // rogue prefers 2G downgrade
                random_int(890, 960) + 0.2,    // fictional GSM-band frequency
                random_int(-45, -30),          // abnormally HIGH power
            ]
        );

        return Cell::find($id) ?? [];
    }

    /**
     * Make N virtual devices attach to the rogue cell and generate detection
     * events + anomalies. Returns a summary of what happened.
     */
    public function lureDevices(int $rogueCellId, int $count = 5): array
    {
        $cell = Cell::find($rogueCellId);
        if ($cell === null || empty($cell['is_rogue'])) {
            throw new \RuntimeException('Cellule rogue introuvable ou non simulée.');
        }

        $devices = Device::random(max(1, min($count, 50)));
        $results = [];

        foreach ($devices as $device) {
            $prevTech = $device['technology'] ?? '4G';
            $signal = random_int(-60, -40); // strong because high power

            Device::moveToCell((int) $device['id'], $rogueCellId, $signal);

            $eventId = ConnectionEvent::log([
                'device_id'  => (int) $device['id'],
                'cell_id'    => $rogueCellId,
                'sim_id'     => $device['sim_id'] ?? null,
                'event_type' => 'attach',
                'technology' => $cell['technology'],
                'signal_dbm' => $signal,
                'risk_level' => 'HIGH',
                'detail'     => 'Attachement à une cellule rogue simulée',
            ]);

            $analysis = $this->engine->analyze($cell, [
                'previous_technology' => $prevTech,
                'identity_requested'  => true,
                'sudden_cell_change'  => true,
                'inconsistent_cell_id'=> true,
            ]);

            $results[] = [
                'device_id'   => (int) $device['id'],
                'device_code' => $device['code'],
                'event_id'    => $eventId,
                'analysis'    => $analysis,
            ];
        }

        return [
            'rogue_cell'      => $cell,
            'devices_lured'   => count($results),
            'results'         => $results,
            'simulation'      => true,
        ];
    }
}
