<?php
declare(strict_types=1);

namespace App\Services;

/**
 * DetectionEngine — rule-based analysis of SIMULATED cell/connection data.
 *
 * Operates only on synthetic characteristics stored in the lab DB. It never
 * inspects real radio signals. Each rule contributes a weighted score; the
 * cumulative score maps to LOW / MEDIUM / HIGH / CRITICAL.
 */
final class DetectionEngine
{
    // Score thresholds -> risk level
    private const THRESHOLDS = [
        'CRITICAL' => 80,
        'HIGH'     => 55,
        'MEDIUM'   => 30,
        'LOW'      => 0,
    ];

    /**
     * Analyse a connection context and return findings.
     *
     * @param array $cell   Cell record (with is_rogue, power_dbm, technology, cell_id_num, mcc, mnc...)
     * @param array $ctx    Context: previous_technology, expected_mcc, expected_mnc,
     *                      known_cell_ids (array), device signal, etc.
     * @return array{score:int, risk_level:string, findings:array, anomaly_type:string}
     */
    /**
     * Classification priority when several rules fire at once. A confirmed
     * unknown/rogue cell is the strongest root-cause signal, so it wins over
     * secondary symptoms such as a downgrade it also triggers.
     */
    private const TYPE_PRIORITY = [
        'unknown_cell', 'downgrade', 'identity_exposure',
        'cell_spoofing', 'plmn_mismatch', 'high_power',
    ];

    public function analyze(array $cell, array $ctx = []): array
    {
        $score = 0;
        $findings = [];
        $types = [];   // candidate anomaly types, resolved by priority below

        // Rule 1: unknown / rogue cell
        if (!empty($cell['is_rogue']) || (isset($cell['is_legitimate']) && !$cell['is_legitimate'])) {
            $score += 45;
            $findings[] = ['rule' => 'unknown_cell', 'weight' => 45,
                'reason' => 'Cellule non répertoriée dans la base légitime (rogue simulée)'];
            $types[] = 'unknown_cell';
        } elseif (!empty($ctx['known_cell_ids']) && !in_array((int) $cell['cell_id_num'], $ctx['known_cell_ids'], true)) {
            $score += 20;
            $findings[] = ['rule' => 'unlisted_cell_id', 'weight' => 20,
                'reason' => 'Cell ID absent de la liste connue'];
            $types[] = 'unknown_cell';
        }

        // Rule 2: abnormally high transmit power (attracts devices)
        $power = (int) ($cell['power_dbm'] ?? -70);
        if ($power > -50) {
            $score += 25;
            $findings[] = ['rule' => 'high_power', 'weight' => 25,
                'reason' => "Puissance anormalement élevée ({$power} dBm)"];
            $types[] = 'high_power';
        } elseif ($power > -60) {
            $score += 10;
            $findings[] = ['rule' => 'elevated_power', 'weight' => 10,
                'reason' => "Puissance élevée ({$power} dBm)"];
        }

        // Rule 3: technology downgrade (e.g. 4G -> 2G)
        $prev = $ctx['previous_technology'] ?? null;
        $tech = $cell['technology'] ?? null;
        if ($prev !== null && $tech !== null) {
            $rank = ['2G' => 1, '3G' => 2, '4G' => 3, '5G' => 4];
            $prevR = $rank[$prev] ?? 0;
            $curR  = $rank[$tech] ?? 0;
            if ($curR < $prevR) {
                $drop = $prevR - $curR;
                $isTo2G = ($tech === '2G');
                $weight = $isTo2G ? 35 : (15 * $drop);
                $score += $weight;
                $findings[] = ['rule' => 'downgrade', 'weight' => $weight,
                    'reason' => "Downgrade technologique {$prev} → {$tech}" . ($isTo2G ? ' (2G non chiffré)' : '')];
                $types[] = 'downgrade';
            }
        }

        // Rule 4: unexpected MCC/MNC (PLMN mismatch)
        if (isset($ctx['expected_mcc'], $cell['mcc']) && (string) $ctx['expected_mcc'] !== (string) $cell['mcc']) {
            $score += 20;
            $findings[] = ['rule' => 'mcc_mismatch', 'weight' => 20,
                'reason' => "MCC inattendu ({$cell['mcc']} au lieu de {$ctx['expected_mcc']})"];
            $types[] = 'plmn_mismatch';
        }
        if (isset($ctx['expected_mnc'], $cell['mnc']) && (string) $ctx['expected_mnc'] !== (string) $cell['mnc']) {
            $score += 15;
            $findings[] = ['rule' => 'mnc_mismatch', 'weight' => 15,
                'reason' => "MNC inattendu ({$cell['mnc']})"];
            $types[] = 'plmn_mismatch';
        }

        // Rule 5: sudden cell change / implausible mobility
        if (!empty($ctx['sudden_cell_change'])) {
            $score += 15;
            $findings[] = ['rule' => 'sudden_cell_change', 'weight' => 15,
                'reason' => 'Changement de cellule inhabituel/soudain'];
        }

        // Rule 6: identity request on attach (IMSI catcher behaviour, simulated)
        if (!empty($ctx['identity_requested'])) {
            $score += 20;
            $findings[] = ['rule' => 'identity_request', 'weight' => 20,
                'reason' => "Requête d'identité (IMSI) inhabituelle à l'attachement"];
            $types[] = 'identity_exposure';
        }

        // Rule 7: inconsistent Cell ID / LAC combination
        if (!empty($ctx['inconsistent_cell_id'])) {
            $score += 15;
            $findings[] = ['rule' => 'inconsistent_cell_id', 'weight' => 15,
                'reason' => 'Cell ID incohérent avec le LAC/TAC annoncé'];
            $types[] = 'cell_spoofing';
        }

        $score = min(100, $score);
        return [
            'score'        => $score,
            'risk_level'   => $this->level($score),
            'findings'     => $findings,
            'anomaly_type' => $this->resolveType($types),
            'simulation'   => true,
        ];
    }

    /** Pick the highest-priority anomaly type among those that fired. */
    private function resolveType(array $types): string
    {
        foreach (self::TYPE_PRIORITY as $candidate) {
            if (in_array($candidate, $types, true)) {
                return $candidate;
            }
        }
        return 'normal';
    }

    public function level(int $score): string
    {
        foreach (self::THRESHOLDS as $level => $min) {
            if ($score >= $min) {
                return $level;
            }
        }
        return 'LOW';
    }
}
