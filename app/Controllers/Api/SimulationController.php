<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Services\DetectionEngine;
use App\Services\RogueCellSimulator;
use App\Services\ScenarioRunner;

final class SimulationController extends Controller
{
    private const VALID_SCENARIOS = [
        'rogue_cell', 'downgrade', 'identity_exposure',
        'location_tracking', 'cell_spoofing',
    ];

    /** Spawn a simulated rogue base station. */
    public function spawnRogue(Request $request): void
    {
        $this->requirePermission($request, 'simulation.run');
        $sim = new RogueCellSimulator();
        $cell = $sim->spawn();
        $this->audit('spawn_rogue_cell', ['cell' => $cell['code'] ?? null], 'rogue_cell');
        $this->json(['ok' => true, 'rogue_cell' => $cell, 'simulation' => true], 201);
    }

    /** Lure virtual devices to a rogue cell. */
    public function lure(Request $request): void
    {
        $this->requirePermission($request, 'simulation.run');
        $cellId = (int) $request->input('cell_id', 0);
        $count = (int) $request->input('count', 5);
        if ($cellId <= 0) {
            $this->json(['error' => 'cell_id requis'], 422);
        }
        $sim = new RogueCellSimulator();
        $result = $sim->lureDevices($cellId, $count);
        $this->audit('lure_devices', ['cell_id' => $cellId, 'count' => $result['devices_lured']], 'rogue_cell');
        $this->json(['ok' => true, 'result' => $result, 'simulation' => true]);
    }

    /** Run a full attack scenario and return the replay timeline. */
    public function runScenario(Request $request): void
    {
        $this->requirePermission($request, 'simulation.run');
        $category = (string) $request->input('scenario', 'rogue_cell');
        if (!in_array($category, self::VALID_SCENARIOS, true)) {
            $this->json(['error' => 'Scénario inconnu', 'valid' => self::VALID_SCENARIOS], 422);
        }
        $runner = new ScenarioRunner();
        $result = $runner->run($category);
        $this->audit('run_scenario', ['scenario' => $category, 'risk' => $result['analysis']['risk_level']], $category);
        $this->json(['ok' => true, 'result' => $result, 'simulation' => true]);
    }

    /** Ad-hoc detection analysis over provided (simulated) characteristics. */
    public function analyze(Request $request): void
    {
        $this->requireAuth($request);
        $cell = [
            'is_rogue'    => (int) $request->input('is_rogue', 0),
            'power_dbm'   => (int) $request->input('power_dbm', -70),
            'technology'  => (string) $request->input('technology', '4G'),
            'cell_id_num' => (int) $request->input('cell_id_num', 100),
            'mcc'         => (string) $request->input('mcc', '001'),
            'mnc'         => (string) $request->input('mnc', '01'),
        ];
        $ctx = [
            'previous_technology' => $request->input('previous_technology'),
            'expected_mcc'        => $request->input('expected_mcc'),
            'identity_requested'  => (bool) $request->input('identity_requested', false),
            'sudden_cell_change'  => (bool) $request->input('sudden_cell_change', false),
            'inconsistent_cell_id'=> (bool) $request->input('inconsistent_cell_id', false),
        ];
        $engine = new DetectionEngine();
        $this->json(['analysis' => $engine->analyze($cell, $ctx), 'simulation' => true]);
    }
}
