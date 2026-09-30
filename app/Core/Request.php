<?php
declare(strict_types=1);

namespace App\Core;

/** Encapsulates the incoming HTTP request. */
final class Request
{
    public string $method;
    public string $path;
    public array $query;
    public array $body;
    public array $headers;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $this->path = '/' . trim($uri, '/');
        $this->query = $_GET;
        $this->headers = self::collectHeaders();

        $raw = file_get_contents('php://input') ?: '';
        $ctype = $this->headers['content-type'] ?? '';
        if (str_contains($ctype, 'application/json') && $raw !== '') {
            $decoded = json_decode($raw, true);
            $this->body = is_array($decoded) ? $decoded : [];
        } else {
            $this->body = $_POST;
        }
    }

    private static function collectHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($k, 5)));
                $headers[$name] = $v;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        return $headers;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function wantsJson(): bool
    {
        $accept = $this->headers['accept'] ?? '';
        return str_starts_with($this->path, '/api/')
            || str_contains($accept, 'application/json');
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
