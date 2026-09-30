<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Base active-record-ish model. */
abstract class Model
{
    protected static string $table = '';

    public static function all(int $limit = 500, int $offset = 0): array
    {
        $limit = max(1, min($limit, 5000));
        return Database::all(
            'SELECT * FROM ' . static::$table . ' ORDER BY id DESC LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM ' . static::$table . ' WHERE id = ?', [$id]);
    }

    public static function count(): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM ' . static::$table);
    }

    public static function deleteAll(): void
    {
        Database::run('DELETE FROM ' . static::$table);
    }
}
