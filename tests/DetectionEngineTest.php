<?php
declare(strict_types=1);

use App\Services\DetectionEngine;
use App\Services\SocExporter;
use App\Services\ScoringService;

/** @return void — appends assertions to the global T counters. */
return static function (): void {
    $e = new DetectionEngine();

    // Legitimate cell -> no risk
    $a = $e->analyze(['is_rogue' => 0, 'is_legitimate' => 1, 'power_dbm' => -80,
        'technology' => '4G', 'cell_id_num' => 100, 'mcc' => '901', 'mnc' => '01']);
    T::ok('legit cell = LOW/0', $a['risk_level'] === 'LOW' && $a['score'] === 0);

    // Full rogue attack -> CRITICAL, classified as unknown_cell (priority)
    $a = $e->analyze(
        ['is_rogue' => 1, 'is_legitimate' => 0, 'power_dbm' => -40, 'technology' => '2G',
         'cell_id_num' => 64000, 'mcc' => '901', 'mnc' => '01'],
        ['previous_technology' => '4G', 'identity_requested' => true, 'inconsistent_cell_id' => true]
    );
    T::eq('rogue attack risk', 'CRITICAL', $a['risk_level']);
    T::eq('rogue attack type', 'unknown_cell', $a['anomaly_type']);

    // Pure downgrade classification
    $a = $e->analyze(['is_rogue' => 0, 'is_legitimate' => 1, 'power_dbm' => -80,
        'technology' => '2G', 'cell_id_num' => 100, 'mcc' => '901'], ['previous_technology' => '4G']);
    T::ok('downgrade scored >=30', $a['score'] >= 30);
    T::eq('downgrade type', 'downgrade', $a['anomaly_type']);

    // High power only
    $a = $e->analyze(['is_rogue' => 0, 'is_legitimate' => 1, 'power_dbm' => -40,
        'technology' => '4G', 'cell_id_num' => 100]);
    T::eq('high power score', 25, $a['score']);

    // PLMN mismatch
    $a = $e->analyze(['is_rogue' => 0, 'is_legitimate' => 1, 'power_dbm' => -80,
        'technology' => '4G', 'cell_id_num' => 100, 'mcc' => '999'], ['expected_mcc' => '901']);
    T::eq('plmn mismatch type', 'plmn_mismatch', $a['anomaly_type']);

    // Thresholds
    T::eq('level 85', 'CRITICAL', $e->level(85));
    T::eq('level 60', 'HIGH', $e->level(60));
    T::eq('level 40', 'MEDIUM', $e->level(40));
    T::eq('level 10', 'LOW', $e->level(10));

    // Score cap + simulation flag
    $a = $e->analyze(['is_rogue' => 1, 'is_legitimate' => 0, 'power_dbm' => -40, 'technology' => '2G',
        'cell_id_num' => 64000, 'mcc' => '999', 'mnc' => '99'],
        ['previous_technology' => '5G', 'expected_mcc' => '901', 'expected_mnc' => '01',
         'identity_requested' => true, 'sudden_cell_change' => true, 'inconsistent_cell_id' => true]);
    T::eq('score capped', 100, $a['score']);
    T::ok('analysis simulation=true', $a['simulation'] === true);

    // SOC exporter mandatory markers
    $soc = new SocExporter();
    $p = $soc->buildPayload(['event_type' => 'ROGUE_CELL_DETECTED', 'severity' => 'HIGH',
        'device_code' => 'DEVICE-00042', 'cell_code' => 'CELL-ROGUE-001', 'message' => 'x']);
    T::ok('SOC simulation=true', $p['simulation'] === true);
    T::ok('SOC lab=true', $p['lab'] === true);
    T::ok('SOC timestamp present', !empty($p['timestamp']));
    T::ok('SOC toJson has simulation', str_contains($soc->toJson(['event_type' => 'X', 'severity' => 'LOW']), '"simulation": true'));

    // Scoring objectives sum to 100
    T::eq('scoring max = 100', 100, array_sum(ScoringService::OBJECTIVES));
};
