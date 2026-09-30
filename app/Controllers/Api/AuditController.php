<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\Core\Controller;
use App\Core\Request;
use App\Models\AuditLog;
final class AuditController extends Controller {
    public function index(Request $request): void {
        $this->requirePermission($request, 'audit.view');
        $this->json(['data' => AuditLog::recent(200), 'simulation' => true]);
    }
}
