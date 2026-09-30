<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Alert;
use App\Models\Anomaly;
use App\Models\Bts;
use App\Models\Cell;
use App\Models\ConnectionEvent;
use App\Models\Device;
use App\Models\Operator;

final class ResourceController extends Controller
{
    public function devices(Request $request): void
    {
        $this->requireAuth($request);
        $this->json(['data' => Device::withContext(500), 'simulation' => true]);
    }

    public function cells(Request $request): void
    {
        $this->requireAuth($request);
        $this->json(['data' => Cell::withContext(1000), 'simulation' => true]);
    }

    public function bts(Request $request): void
    {
        $this->requireAuth($request);
        $this->json(['data' => Bts::withOperator(500), 'simulation' => true]);
    }

    public function operators(Request $request): void
    {
        $this->requireAuth($request);
        $this->json(['data' => Operator::all(100), 'simulation' => true]);
    }

    public function events(Request $request): void
    {
        $this->requireAuth($request);
        $this->json(['data' => ConnectionEvent::recent(200), 'simulation' => true]);
    }

    public function anomalies(Request $request): void
    {
        $this->requireAuth($request);
        $this->json(['data' => Anomaly::recent(200), 'simulation' => true]);
    }

    public function alerts(Request $request): void
    {
        $this->requireAuth($request);
        $this->json(['data' => Alert::recent(200), 'simulation' => true]);
    }

    /** Data for the Leaflet map: BTS, cells, devices, rogue cells. */
    public function mapData(Request $request): void
    {
        $this->requireAuth($request);
        $bts = Database::all('SELECT id, code, lat, lng, coverage_m, is_legitimate FROM bts');
        $devices = Database::all(
            'SELECT id, code, lat, lng, signal_dbm, connection_state FROM devices
             WHERE lat IS NOT NULL AND lng IS NOT NULL LIMIT 500'
        );
        $rogues = Database::all(
            'SELECT c.id, c.code, c.power_dbm, c.technology, b.lat, b.lng
             FROM cells c JOIN bts b ON b.id = c.bts_id WHERE c.is_rogue = 1'
        );
        $this->json([
            'bts'        => $bts,
            'devices'    => $devices,
            'rogues'     => $rogues,
            'note'       => 'Coordonnées entièrement artificielles — aucune localisation réelle.',
            'simulation' => true,
        ]);
    }

    /** Data for Cytoscape graph: PHONE -> SIM -> CELL -> BTS -> OPERATOR. */
    public function graphData(Request $request): void
    {
        $this->requireAuth($request);
        $limit = (int) ($request->query['limit'] ?? 30);
        $limit = max(1, min($limit, 100));

        $rows = Database::all(
            'SELECT d.id AS did, d.code AS device, s.id AS sid, s.code AS sim,
                    c.id AS cid, c.code AS cell, c.is_rogue,
                    b.id AS bid, b.code AS bts, o.id AS oid, o.name AS operator
             FROM devices d
             LEFT JOIN sims s ON s.id = d.sim_id
             LEFT JOIN cells c ON c.id = d.current_cell_id
             LEFT JOIN bts b ON b.id = c.bts_id
             LEFT JOIN operators o ON o.id = c.operator_id
             WHERE d.current_cell_id IS NOT NULL
             LIMIT ?',
            [$limit]
        );

        $nodes = [];
        $edges = [];
        $add = function (string $id, string $label, string $type) use (&$nodes): void {
            if (!isset($nodes[$id])) {
                $nodes[$id] = ['data' => ['id' => $id, 'label' => $label, 'type' => $type]];
            }
        };

        foreach ($rows as $r) {
            $dev = 'd' . $r['did'];
            $add($dev, $r['device'], 'phone');
            if ($r['sid']) {
                $sim = 's' . $r['sid'];
                $add($sim, $r['sim'], 'sim');
                $edges[] = ['data' => ['source' => $dev, 'target' => $sim]];
                $parent = $sim;
            } else {
                $parent = $dev;
            }
            if ($r['cid']) {
                $cell = 'c' . $r['cid'];
                $add($cell, $r['cell'], $r['is_rogue'] ? 'rogue' : 'cell');
                $edges[] = ['data' => ['source' => $parent, 'target' => $cell]];
                if ($r['bid']) {
                    $bts = 'b' . $r['bid'];
                    $add($bts, $r['bts'], 'bts');
                    $edges[] = ['data' => ['source' => $cell, 'target' => $bts]];
                    if ($r['oid']) {
                        $op = 'o' . $r['oid'];
                        $add($op, $r['operator'], 'operator');
                        $edges[] = ['data' => ['source' => $bts, 'target' => $op]];
                    }
                }
            }
        }

        $this->json([
            'elements'   => ['nodes' => array_values($nodes), 'edges' => $edges],
            'simulation' => true,
        ]);
    }
}
