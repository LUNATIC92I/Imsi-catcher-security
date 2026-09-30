<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/** Session-based authentication + RBAC. */
final class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $cfg = App::config('security');
        session_name($cfg['session_name']);
        session_set_cookie_params([
            'lifetime' => $cfg['session_lifetime'],
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
        ]);
        session_start();
    }

    public static function attempt(string $username, string $password): bool
    {
        $user = User::findByUsername($username);
        if ($user === null || !(int) $user['is_active']) {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        // Rehash if needed.
        if (password_needs_rehash($user['password_hash'], App::config('security')['password_algo'])) {
            User::updatePassword((int) $user['id'], $password);
        }
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'       => (int) $user['id'],
            'username' => $user['username'],
            'role'     => $user['role_name'],
            'perms'    => json_decode($user['permissions'] ?? '[]', true) ?: [],
        ];
        User::touchLogin((int) $user['id']);
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function can(string $permission): bool
    {
        $perms = $_SESSION['user']['perms'] ?? [];
        return in_array('*', $perms, true) || in_array($permission, $perms, true);
    }

    public static function hasRole(string $role): bool
    {
        return ($_SESSION['user']['role'] ?? '') === $role;
    }
}
