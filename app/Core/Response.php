<?php
declare(strict_types=1);

namespace App\Core;

/** Helpers for emitting responses with security headers. */
final class Response
{
    public static function securityHeaders(): void
    {
        $csp = App::config('security')['csp'] ?? "default-src 'self'";
        header('Content-Security-Policy: ' . $csp);
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function error(string $message, int $status = 400, array $extra = []): never
    {
        self::json(array_merge(['error' => $message, 'status' => $status], $extra), $status);
    }

    public static function redirect(string $to, int $status = 302): never
    {
        header('Location: ' . $to, true, $status);
        exit;
    }
}
