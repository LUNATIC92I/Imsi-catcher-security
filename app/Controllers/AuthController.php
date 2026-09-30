<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;

final class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect('/dashboard');
        }
        $this->view('pages/login', ['title' => 'Connexion'], 'auth');
    }

    public function login(Request $request): void
    {
        $cfg = App::config('security')['login_rate_limit'];
        if (!RateLimiter::allow('login:' . $request->ip(), $cfg['max'], $cfg['window'])) {
            Response::error('Trop de tentatives de connexion. Réessayez plus tard.', 429);
        }

        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');

        if ($username === '' || $password === '') {
            Response::error('Identifiants requis', 422);
        }

        if (!Auth::attempt($username, $password)) {
            $this->audit('login_failed', ['username' => $username], null, 'failed');
            Response::error('Identifiants invalides', 401);
        }

        $this->audit('login_success', ['username' => $username]);
        $this->json(['ok' => true, 'redirect' => '/dashboard']);
    }

    public function logout(Request $request): void
    {
        $this->audit('logout');
        Auth::logout();
        Response::redirect('/login');
    }
}
