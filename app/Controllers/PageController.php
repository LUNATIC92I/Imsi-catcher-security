<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

/** Renders the SPA-like HTML shells; data is loaded via the REST API. */
final class PageController extends Controller
{
    public function home(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect('/dashboard');
        }
        Response::redirect('/login');
    }

    public function dashboard(Request $request): void
    {
        $this->requireAuth($request);
        $this->audit('view_dashboard');
        $this->view('pages/dashboard', ['title' => 'Dashboard', 'active' => 'dashboard']);
    }

    public function inventory(Request $request): void
    {
        $this->requireAuth($request);
        $this->audit('view_inventory');
        $this->view('pages/inventory', ['title' => 'Inventaire réseau', 'active' => 'inventory']);
    }

    public function map(Request $request): void
    {
        $this->requireAuth($request);
        $this->view('pages/map', ['title' => 'Carte', 'active' => 'map']);
    }

    public function graph(Request $request): void
    {
        $this->requireAuth($request);
        $this->view('pages/graph', ['title' => 'Graph Analysis', 'active' => 'graph']);
    }

    public function replay(Request $request): void
    {
        $this->requireAuth($request);
        $this->view('pages/replay', ['title' => 'Attack Replay', 'active' => 'replay']);
    }

    public function soc(Request $request): void
    {
        $this->requireAuth($request);
        $this->view('pages/soc', ['title' => 'SOC Integration', 'active' => 'soc']);
    }

    public function training(Request $request): void
    {
        $this->requireAuth($request);
        $this->view('pages/training', ['title' => 'Training', 'active' => 'training']);
    }

    public function auditPage(Request $request): void
    {
        $this->requirePermission($request, 'audit.view');
        $this->view('pages/audit', ['title' => 'Audit', 'active' => 'audit']);
    }
}
