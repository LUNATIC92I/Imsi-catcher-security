<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;

final class CsrfMiddleware
{
    public function handle(Request $request): void
    {
        if (!Csrf::validate($request)) {
            Response::error('Jeton CSRF invalide ou manquant', 419);
        }
    }
}
