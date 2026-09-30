<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Application container: holds config and bootstraps the autoloader.
 */
final class App
{
    private static array $config = [];

    public static function boot(): void
    {
        self::$config = require dirname(__DIR__, 2) . '/config/config.php';

        // PSR-4-ish autoloader for the App\ namespace.
        spl_autoload_register(static function (string $class): void {
            $prefix = 'App\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $file = dirname(__DIR__) . '/' . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require $file;
            }
        });

        // Error handling.
        $debug = self::config('app')['debug'] ?? false;
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', self::config('paths')['logs'] . '/php-error.log');
    }

    /** @return mixed */
    public static function config(?string $key = null)
    {
        if ($key === null) {
            return self::$config;
        }
        return self::$config[$key] ?? null;
    }

    public static function isDebug(): bool
    {
        return (bool) (self::config('app')['debug'] ?? false);
    }
}
