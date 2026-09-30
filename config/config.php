<?php
declare(strict_types=1);

/*
 * LUNATIC MOBILE SECURITY LAB — central configuration.
 *
 * This is a SIMULATION-ONLY training platform. It never touches real
 * cellular networks, SDR hardware, or real subscriber identifiers.
 * Every telecom identifier handled here is synthetic and flagged is_simulated=1.
 */

return [
    'app' => [
        'name'     => 'LUNATIC MOBILE SECURITY LAB',
        'tagline'  => 'Mobile Network Security Simulation Platform',
        'env'      => getenv('APP_ENV') ?: 'local',
        'debug'    => filter_var(getenv('APP_DEBUG') ?: 'true', FILTER_VALIDATE_BOOL),
        'base_url' => getenv('APP_BASE_URL') ?: '',
        // Hard guarantee, enforced across the codebase.
        'simulation_only' => true,
    ],

    'db' => [
        'host'    => getenv('DB_HOST') ?: '127.0.0.1',
        'port'    => (int)(getenv('DB_PORT') ?: 3306),
        'name'    => getenv('DB_NAME') ?: 'lunatic_lab',
        'user'    => getenv('DB_USER') ?: 'lunatic',
        'pass'    => getenv('DB_PASS') ?: 'lunatic_secret',
        'charset' => 'utf8mb4',
    ],

    'security' => [
        // Sessions
        'session_name'      => 'LUNATIC_SID',
        'session_lifetime'  => 3600,
        // Password hashing
        'password_algo'     => PASSWORD_ARGON2ID,
        // Rate limiting (per identifier, sliding window)
        'rate_limit'        => ['window' => 60, 'max' => 120],
        'login_rate_limit'  => ['window' => 300, 'max' => 10],
        // CSP served by the app layer
        'csp' => "default-src 'self'; "
               . "script-src 'self' https://cdn.jsdelivr.net https://unpkg.com; "
               . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; "
               . "img-src 'self' data: https://*.tile.openstreetmap.org https://unpkg.com; "
               . "font-src 'self' https://cdn.jsdelivr.net; "
               . "connect-src 'self'",
    ],

    'paths' => [
        'root'    => dirname(__DIR__),
        'app'     => dirname(__DIR__) . '/app',
        'views'   => dirname(__DIR__) . '/app/Views',
        'storage' => dirname(__DIR__) . '/storage',
        'logs'    => dirname(__DIR__) . '/storage/logs',
    ],
];
