<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\App;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;

final class RateLimitMiddleware
{
    public function handle(Request $request): void
    {
        $cfg = App::config('security')['rate_limit'];
        $bucket = 'api:' . $request->ip();
        if (!RateLimiter::allow($bucket, $cfg['max'], $cfg['window'])) {
            Response::error('Trop de requêtes (rate limit)', 429);
        }
    }
}
