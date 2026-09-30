<?php
declare(strict_types=1);

/**
 * LUNATIC MOBILE SECURITY LAB — data generator / seeder.
 *
 * Generates a fully SYNTHETIC dataset. Every identifier is fictional and
 * flagged is_simulated = 1. No real IMSI/IMEI/subscriber data is ever used.
 *
 * Usage:  php database/seed.php
 *
 * Volumes: 10 operators, 100 BTS, 300 cells, 1000 devices, 1000 SIMs,
 *          10000 events, 500 anomalies, 100 scenarios, + demo users.
 */

require dirname(__DIR__) . '/app/Core/App.php';

use App\Core\App;
use App\Core\Database;

App::boot();
$pdo = Database::connection();

function out(string $m): void { echo $m . PHP_EOL; }

out('== LUNATIC seeder (simulation-only) ==');

// Reset (children first) --------------------------------------------------
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach ([
    'quiz_results', 'lab_scores', 'audit_logs', 'rate_limits',
    'alerts', 'anomalies', 'handovers', 'connection_events',
    'device_mobility', 'devices', 'sims', 'cells', 'bts', 'operators',
    'scenario_steps', 'scenarios', 'users', 'roles',
] as $t) {
    $pdo->exec("TRUNCATE TABLE {$t}");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
out('· tables truncated');

// RBAC roles --------------------------------------------------------------
$roles = [
    ['admin',   'Administrateur', ['*']],
    ['analyst', 'Analyste SOC',   ['simulation.run', 'soc.push', 'soc.respond', 'audit.view']],
    ['student', 'Étudiant',       ['simulation.run']],
];
$rStmt = $pdo->prepare('INSERT INTO roles (name, label, permissions) VALUES (?,?,?)');
$roleIds = [];
foreach ($roles as [$name, $label, $perms]) {
    $rStmt->execute([$name, $label, json_encode($perms)]);
    $roleIds[$name] = (int) $pdo->lastInsertId();
}
out('· roles created');

// Demo users --------------------------------------------------------------
$users = [
    ['admin',   'admin@lunatic.lab',   'admin1234',   'admin'],
    ['analyst', 'analyst@lunatic.lab', 'analyst1234', 'analyst'],
    ['student', 'student@lunatic.lab', 'student1234', 'student'],
];
$uStmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role_id) VALUES (?,?,?,?)');
foreach ($users as [$u, $e, $p, $r]) {
    $uStmt->execute([$u, $e, password_hash($p, PASSWORD_ARGON2ID), $roleIds[$r]]);
}
out('· demo users: admin/admin1234, analyst/analyst1234, student/student1234');

// Operators (fictional PLMN) ---------------------------------------------
$opStmt = $pdo->prepare(
    'INSERT INTO operators (code, name, mcc, mnc, country) VALUES (?,?,?,?,?)'
);
$operatorIds = [];
for ($i = 1; $i <= 10; $i++) {
    $code = sprintf('OPERATOR-LAB-%02d', $i);
    $mcc = (string) (900 + $i);                 // 9xx = test/fictional range
    $mnc = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
    $opStmt->execute([$code, 'LabTel ' . chr(64 + $i), $mcc, $mnc, 'Labland']);
    $operatorIds[] = (int) $pdo->lastInsertId();
}
out('· 10 operators');

// BTS around an artificial center ----------------------------------------
$centerLat = 48.8566; $centerLng = 2.3522; // arbitrary anchor, purely visual
$btsStmt = $pdo->prepare(
    'INSERT INTO bts (code, operator_id, lat, lng, coverage_m, is_legitimate) VALUES (?,?,?,?,?,1)'
);
$btsIds = [];
for ($i = 1; $i <= 100; $i++) {
    $lat = $centerLat + (mt_rand(-800, 800) / 10000);
    $lng = $centerLng + (mt_rand(-800, 800) / 10000);
    $btsStmt->execute([
        sprintf('BTS-%03d', $i),
        $operatorIds[array_rand($operatorIds)],
        round($lat, 6), round($lng, 6),
        mt_rand(800, 2500),
    ]);
    $btsIds[] = (int) $pdo->lastInsertId();
}
out('· 100 BTS');

// Cells -------------------------------------------------------------------
$techWeights = ['4G', '4G', '4G', '3G', '3G', '2G', '5G'];
$cellStmt = $pdo->prepare(
    'INSERT INTO cells (code, bts_id, operator_id, cell_id_num, lac, technology, frequency_mhz, power_dbm, is_legitimate, is_rogue)
     VALUES (?,?,?,?,?,?,?,?,?,?)'
);
$cellIds = [];
$pdo->beginTransaction();
for ($i = 1; $i <= 300; $i++) {
    $bts = $btsIds[array_rand($btsIds)];
    $op = $operatorIds[array_rand($operatorIds)];
    $tech = $techWeights[array_rand($techWeights)];
    $freq = match ($tech) {
        '2G' => mt_rand(890, 960) + 0.2,
        '3G' => mt_rand(1920, 2170) + 0.2,
        '4G' => mt_rand(1800, 2600) + 0.2,
        '5G' => mt_rand(3400, 3800) + 0.2,
    };
    // ~4% simulated rogue cells sprinkled in.
    $isRogue = (mt_rand(1, 100) <= 4) ? 1 : 0;
    $power = $isRogue ? mt_rand(-48, -32) : mt_rand(-95, -60);
    $cellStmt->execute([
        $isRogue ? sprintf('CELL-ROGUE-%03d', $i) : sprintf('CELL-%03d', $i),
        $bts, $op,
        $isRogue ? mt_rand(60000, 65000) : mt_rand(1000, 59000),
        mt_rand(1, 300),
        $isRogue ? '2G' : $tech,
        $freq, $power,
        $isRogue ? 0 : 1,
        $isRogue,
    ]);
    $cellIds[] = (int) $pdo->lastInsertId();
}
$pdo->commit();
out('· 300 cells (incl. simulated rogue cells)');

// SIMs --------------------------------------------------------------------
$simStmt = $pdo->prepare(
    'INSERT INTO sims (code, operator_id, imsi_fake) VALUES (?,?,?)'
);
$simIds = [];
$pdo->beginTransaction();
for ($i = 1; $i <= 1000; $i++) {
    $op = $operatorIds[array_rand($operatorIds)];
    // Fictional IMSI: FAKE prefix + 9xx MCC + padded serial — NOT a real IMSI.
    $imsi = 'FAKE-' . (900 + mt_rand(1, 10)) . str_pad((string) $i, 10, '0', STR_PAD_LEFT);
    $simStmt->execute([sprintf('SIM-%04d', $i), $op, $imsi]);
    $simIds[] = (int) $pdo->lastInsertId();
}
$pdo->commit();
out('· 1000 SIMs (fictional IMSI)');

// Devices -----------------------------------------------------------------
$models = ['VirtualPhone', 'LabDroid', 'SimPhone-X', 'TestHandset', 'EduMobile'];
$states = ['idle', 'connected', 'searching'];
$devStmt = $pdo->prepare(
    'INSERT INTO devices (code, sim_id, imei_fake, model, current_cell_id, signal_dbm, connection_state, lat, lng)
     VALUES (?,?,?,?,?,?,?,?,?)'
);
$deviceIds = [];
$pdo->beginTransaction();
for ($i = 1; $i <= 1000; $i++) {
    $cell = $cellIds[array_rand($cellIds)];
    // Fictional IMEI: FAKE prefix — NOT a valid/real IMEI.
    $imei = 'FAKE-IMEI-' . str_pad((string) mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
    $lat = $centerLat + (mt_rand(-900, 900) / 10000);
    $lng = $centerLng + (mt_rand(-900, 900) / 10000);
    $devStmt->execute([
        sprintf('DEVICE-%04d', $i),
        $simIds[$i - 1] ?? null,
        $imei, $models[array_rand($models)],
        $cell, mt_rand(-105, -55),
        $states[array_rand($states)],
        round($lat, 6), round($lng, 6),
    ]);
    $deviceIds[] = (int) $pdo->lastInsertId();
}
$pdo->commit();
out('· 1000 virtual devices (fictional IMEI)');

// Connection events -------------------------------------------------------
$eventTypes = ['attach', 'detach', 'handover', 'location_update', 'paging', 'identity_request', 'downgrade', 'reject'];
$risks = ['LOW', 'LOW', 'LOW', 'MEDIUM', 'MEDIUM', 'HIGH', 'CRITICAL'];
$techs = ['2G', '3G', '4G', '5G'];
$evStmt = $pdo->prepare(
    'INSERT INTO connection_events (device_id, cell_id, sim_id, event_type, technology, signal_dbm, risk_level, detail, created_at)
     VALUES (?,?,?,?,?,?,?,?,?)'
);
$pdo->beginTransaction();
for ($i = 1; $i <= 10000; $i++) {
    $dev = $deviceIds[array_rand($deviceIds)];
    $cell = $cellIds[array_rand($cellIds)];
    $type = $eventTypes[array_rand($eventTypes)];
    $risk = ($type === 'downgrade' || $type === 'identity_request') ? 'HIGH' : $risks[array_rand($risks)];
    $ts = date('Y-m-d H:i:s', time() - mt_rand(0, 86400)); // last 24h
    $evStmt->execute([
        $dev, $cell, null, $type, $techs[array_rand($techs)],
        mt_rand(-110, -50), $risk, 'Événement réseau simulé', $ts,
    ]);
    if ($i % 2000 === 0) { $pdo->commit(); $pdo->beginTransaction(); }
}
$pdo->commit();
out('· 10000 connection events');

// Handovers ---------------------------------------------------------------
$hoStmt = $pdo->prepare(
    'INSERT INTO handovers (device_id, from_cell_id, to_cell_id, reason) VALUES (?,?,?,?)'
);
$pdo->beginTransaction();
for ($i = 1; $i <= 1200; $i++) {
    $hoStmt->execute([
        $deviceIds[array_rand($deviceIds)],
        $cellIds[array_rand($cellIds)],
        $cellIds[array_rand($cellIds)],
        'Mobilité simulée',
    ]);
}
$pdo->commit();
out('· 1200 handovers');

// Anomalies ---------------------------------------------------------------
$anomTypes = ['unknown_cell', 'downgrade', 'high_power', 'plmn_mismatch', 'cell_spoofing', 'identity_exposure', 'sudden_cell_change'];
$anStmt = $pdo->prepare(
    'INSERT INTO anomalies (device_id, cell_id, anomaly_type, risk_level, score, reason) VALUES (?,?,?,?,?,?)'
);
$pdo->beginTransaction();
for ($i = 1; $i <= 500; $i++) {
    $type = $anomTypes[array_rand($anomTypes)];
    $score = mt_rand(20, 100);
    $level = $score >= 80 ? 'CRITICAL' : ($score >= 55 ? 'HIGH' : ($score >= 30 ? 'MEDIUM' : 'LOW'));
    $anStmt->execute([
        $deviceIds[array_rand($deviceIds)],
        $cellIds[array_rand($cellIds)],
        $type, $level, $score, 'Anomalie détectée (simulation): ' . $type,
    ]);
}
$pdo->commit();
out('· 500 anomalies');

// A handful of open alerts from top anomalies -----------------------------
$topAnoms = $pdo->query(
    "SELECT * FROM anomalies WHERE risk_level IN ('HIGH','CRITICAL') ORDER BY score DESC LIMIT 60"
)->fetchAll();
$alStmt = $pdo->prepare(
    'INSERT INTO alerts (anomaly_id, device_id, cell_id, event_type, severity, status, message, simulation)
     VALUES (?,?,?,?,?,?,?,1)'
);
$typeToEvent = [
    'downgrade' => 'DOWNGRADE_ATTACK_DETECTED',
    'identity_exposure' => 'IMSI_EXPOSURE_DETECTED',
    'cell_spoofing' => 'CELL_SPOOFING_DETECTED',
    'unknown_cell' => 'ROGUE_CELL_DETECTED',
];
foreach ($topAnoms as $a) {
    $alStmt->execute([
        $a['id'], $a['device_id'], $a['cell_id'],
        $typeToEvent[$a['anomaly_type']] ?? 'ANOMALY_DETECTED',
        $a['risk_level'], 'open',
        'Menace simulée détectée: ' . $a['anomaly_type'],
    ]);
}
out('· ' . count($topAnoms) . ' open alerts');

// Scenarios (5 templates x 20 instances = 100) ----------------------------
$templates = [
    ['rogue_cell',        'Rogue Cell',              'rogue_cell'],
    ['downgrade',         'Downgrade 4G → 2G',       'downgrade'],
    ['identity_exposure', 'Identity Exposure',       'identity_exposure'],
    ['location_tracking', 'Location Tracking',       'location_tracking'],
    ['cell_spoofing',     'Cell Spoofing',           'cell_spoofing'],
];
$scStmt = $pdo->prepare(
    'INSERT INTO scenarios (code, name, category, description, difficulty, max_score) VALUES (?,?,?,?,?,100)'
);
$stepStmt = $pdo->prepare(
    'INSERT INTO scenario_steps (scenario_id, step_order, title, description, step_type, payload) VALUES (?,?,?,?,?,?)'
);
$diffs = ['beginner', 'intermediate', 'advanced'];
$n = 0;
$pdo->beginTransaction();
foreach ($templates as [$cat, $name, $slug]) {
    for ($j = 1; $j <= 20; $j++) {
        $n++;
        $code = sprintf('SCENARIO-%s-%02d', strtoupper($slug), $j);
        $scStmt->execute([
            $code, $name . ' #' . $j, $cat,
            'Scénario pédagogique simulé — ' . $name,
            $diffs[array_rand($diffs)],
        ]);
        $sid = (int) $pdo->lastInsertId();
        $steps = [
            ['Téléphone virtuel', 'Appareil en veille', 'phone'],
            ['Cellule légitime', 'Rattachement normal', 'legit_cell'],
            ['Cellule rogue', 'Apparition de la fausse station', 'rogue_cell'],
            ['Changement de cellule', 'Bascule vers la rogue', 'handover'],
            ['Exposition', 'Identifiant exposé', 'exposure'],
            ['Détection', 'Moteur de détection', 'detection'],
            ['Alerte', 'Génération alerte SOC', 'alert'],
            ['Réponse défensive', 'Blocage + incident', 'response'],
        ];
        foreach ($steps as $k => [$t, $d, $type]) {
            $stepStmt->execute([$sid, $k + 1, $t, $d, $type, json_encode(['simulation' => true])]);
        }
    }
}
$pdo->commit();
out("· {$n} attack scenarios (with replay steps)");

out('');
out('== SEED COMPLETE — all data is fictional (simulation:true) ==');
