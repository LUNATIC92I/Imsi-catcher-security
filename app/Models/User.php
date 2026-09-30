<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\App;
use App\Core\Database;

final class User extends Model
{
    protected static string $table = 'users';

    public static function findByUsername(string $username): ?array
    {
        return Database::first(
            'SELECT u.*, r.name AS role_name, r.permissions
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.username = ? LIMIT 1',
            [$username]
        );
    }

    public static function updatePassword(int $id, string $plain): void
    {
        $hash = password_hash($plain, App::config('security')['password_algo']);
        Database::run('UPDATE users SET password_hash = ? WHERE id = ?', [$hash, $id]);
    }

    public static function touchLogin(int $id): void
    {
        Database::run('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?', [$id]);
    }

    public static function create(string $username, string $email, string $plain, int $roleId): int
    {
        $hash = password_hash($plain, App::config('security')['password_algo']);
        return Database::insert(
            'INSERT INTO users (username, email, password_hash, role_id) VALUES (?,?,?,?)',
            [$username, $email, $hash, $roleId]
        );
    }
}
