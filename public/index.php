<?php
declare(strict_types=1);

/**
 * LUNATIC MOBILE SECURITY LAB — front controller.
 * Simulation-only platform. No real cellular network, SDR, or subscriber data.
 */

require dirname(__DIR__) . '/app/Core/App.php';

use App\Core\App;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

App::boot();
Auth::startSession();
Response::securityHeaders();

$router = new Router();
require dirname(__DIR__) . '/config/routes.php';

try {
    $router->dispatch(new Request());
} catch (Throwable $e) {
    error_log('[LUNATIC] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    $request = new Request();
    if ($request->wantsJson()) {
        $payload = ['error' => 'Erreur interne', 'status' => 500];
        if (App::isDebug()) {
            $payload['debug'] = $e->getMessage();
        }
        Response::json($payload, 500);
    }
    http_response_code(500);
    echo '<h1>500 — Erreur interne</h1>';
    if (App::isDebug()) {
        echo '<pre>' . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
}
