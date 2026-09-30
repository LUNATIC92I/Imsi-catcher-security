<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\PageController;
use App\Controllers\Api\AuditController;
use App\Controllers\Api\DefensiveController;
use App\Controllers\Api\ResourceController;
use App\Controllers\Api\ScoringController;
use App\Controllers\Api\SimulationController;
use App\Controllers\Api\SocController;
use App\Controllers\Api\StatsController;
use App\Controllers\Api\TrainingController;
use App\Core\Router;
use App\Middleware\ApiAuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RateLimitMiddleware;

/** @var Router $router */

// --- Public / auth ---
$router->get('/', [PageController::class, 'home']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
$router->post('/logout', [AuthController::class, 'logout'], [CsrfMiddleware::class]);

// --- Pages (auth enforced in controllers) ---
$router->get('/dashboard', [PageController::class, 'dashboard']);
$router->get('/map',       [PageController::class, 'map']);
$router->get('/graph',     [PageController::class, 'graph']);
$router->get('/replay',    [PageController::class, 'replay']);
$router->get('/soc',       [PageController::class, 'soc']);
$router->get('/training',  [PageController::class, 'training']);
$router->get('/audit',     [PageController::class, 'auditPage']);

// --- REST API v1 ---
$api = [RateLimitMiddleware::class, ApiAuthMiddleware::class];
$apiWrite = [RateLimitMiddleware::class, ApiAuthMiddleware::class, CsrfMiddleware::class];

// Stats
$router->get('/api/v1/stats/summary',    [StatsController::class, 'summary'], $api);
$router->get('/api/v1/stats/timeseries', [StatsController::class, 'timeseries'], $api);

// Resources
$router->get('/api/v1/devices',   [ResourceController::class, 'devices'], $api);
$router->get('/api/v1/cells',     [ResourceController::class, 'cells'], $api);
$router->get('/api/v1/bts',       [ResourceController::class, 'bts'], $api);
$router->get('/api/v1/operators', [ResourceController::class, 'operators'], $api);
$router->get('/api/v1/events',    [ResourceController::class, 'events'], $api);
$router->get('/api/v1/anomalies', [ResourceController::class, 'anomalies'], $api);
$router->get('/api/v1/alerts',    [ResourceController::class, 'alerts'], $api);
$router->get('/api/v1/map',       [ResourceController::class, 'mapData'], $api);
$router->get('/api/v1/graph',     [ResourceController::class, 'graphData'], $api);

// Simulation / detection
$router->post('/api/v1/simulation/rogue/spawn', [SimulationController::class, 'spawnRogue'], $apiWrite);
$router->post('/api/v1/simulation/rogue/lure',  [SimulationController::class, 'lure'], $apiWrite);
$router->post('/api/v1/simulation/scenario',    [SimulationController::class, 'runScenario'], $apiWrite);
$router->post('/api/v1/detection/analyze',      [SimulationController::class, 'analyze'], $apiWrite);

// SOC
$router->get('/api/v1/soc/export', [SocController::class, 'export'], $api);
$router->post('/api/v1/soc/push',  [SocController::class, 'push'], $apiWrite);

// Defensive response
$router->post('/api/v1/defensive/respond', [DefensiveController::class, 'respond'], $apiWrite);

// Scoring
$router->post('/api/v1/scoring/award',   [ScoringController::class, 'award'], $apiWrite);
$router->get('/api/v1/scoring/me',       [ScoringController::class, 'me'], $api);
$router->get('/api/v1/scoring/leaderboard', [ScoringController::class, 'leaderboard'], $api);

// Training
$router->post('/api/v1/training/quiz', [TrainingController::class, 'submitQuiz'], $apiWrite);
$router->get('/api/v1/training/results', [TrainingController::class, 'myResults'], $api);

// Audit
$router->get('/api/v1/audit', [AuditController::class, 'index'], $api);
