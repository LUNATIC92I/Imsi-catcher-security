<?php
declare(strict_types=1);

namespace App\Core;

/** DB-backed sliding-window rate limiter. */
final class RateLimiter
{
    /** @return bool true if allowed, false if rate limit exceeded */
    public static function allow(string $bucket, int $max, int $window): bool
    {
        $now = time();
        $windowStart = $now - ($now % $window);
        $key = substr($bucket, 0, 180);

        $row = Database::first(
            'SELECT id, hits, window_start FROM rate_limits WHERE bucket = ?',
            [$key]
        );

        if ($row === null) {
            Database::insert(
                'INSERT INTO rate_limits (bucket, hits, window_start) VALUES (?, 1, ?)',
                [$key, $windowStart]
            );
            return true;
        }

        if ((int) $row['window_start'] !== $windowStart) {
            Database::run(
                'UPDATE rate_limits SET hits = 1, window_start = ? WHERE id = ?',
                [$windowStart, $row['id']]
            );
            return true;
        }

        if ((int) $row['hits'] >= $max) {
            return false;
        }

        Database::run('UPDATE rate_limits SET hits = hits + 1 WHERE id = ?', [$row['id']]);
        return true;
    }
}
