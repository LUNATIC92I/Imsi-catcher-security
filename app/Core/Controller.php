<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\AuditLog;

/** Base controller with shared helpers. */
abstract class Controller
{
    protected function view(string $template, array $data = [], string $layout = 'main'): void
    {
        $data['auth']   = Auth::user();
        $data['csrf']   = Csrf::token();
        $data['appcfg'] = App::config('app');
        View::render($template, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function requireAuth(Request $request): void
    {
        if (!Auth::check()) {
            if ($request->wantsJson()) {
                Response::error('Authentification requise', 401);
            }
            Response::redirect('/login');
        }
    }

    protected function requirePermission(Request $request, string $permission): void
    {
        $this->requireAuth($request);
        if (!Auth::can($permission)) {
            if ($request->wantsJson()) {
                Response::error('Accès refusé (RBAC)', 403);
            }
            http_response_code(403);
            View::render('pages/error', ['code' => 403, 'message' => 'Accès refusé'], 'main');
            exit;
        }
    }

    protected function audit(string $action, array $meta = [], ?string $scenario = null, string $result = 'ok'): void
    {
        AuditLog::record($action, $meta, $scenario, $result);
    }
}
