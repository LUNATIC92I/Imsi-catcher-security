<?php
declare(strict_types=1);

namespace App\Core;

/** CSRF token management (double-submit via session). */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validate(Request $request): bool
    {
        if (in_array($request->method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }
        $sent = $request->headers['x-csrf-token']
            ?? $request->input('csrf_token', '');
        $stored = $_SESSION['csrf_token'] ?? '';
        return $stored !== '' && is_string($sent) && hash_equals($stored, $sent);
    }
}
