<?php
declare(strict_types=1);

namespace App\Services;

/**
 * SocExporter — builds SIEM-style JSON payloads for simulated alerts.
 * Every payload carries the mandatory "simulation": true and "lab": true
 * markers so no downstream system can mistake it for real telemetry.
 */
final class SocExporter
{
    public function buildPayload(array $alert): array
    {
        return [
            'event_type' => $alert['event_type'] ?? 'ROGUE_CELL_DETECTED',
            'severity'   => $alert['severity'] ?? 'HIGH',
            'device_id'  => $alert['device_code'] ?? ('DEVICE-' . str_pad((string) ($alert['device_id'] ?? 0), 5, '0', STR_PAD_LEFT)),
            'cell_id'    => $alert['cell_code'] ?? ('CELL-' . ($alert['cell_id'] ?? 'UNKNOWN')),
            'message'    => $alert['message'] ?? '',
            'timestamp'  => gmdate('c'),
            'source'     => 'LUNATIC-MOBILE-SECURITY-LAB',
            'lab'        => true,
            // Mandatory marker — this is training data, never real telemetry.
            'simulation' => true,
        ];
    }

    public function toJson(array $alert): string
    {
        return json_encode(
            $this->buildPayload($alert),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}
