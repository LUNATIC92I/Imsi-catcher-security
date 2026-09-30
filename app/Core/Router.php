<?php
declare(strict_types=1);

namespace App\Core;

/** Minimal regex router with method + path matching. */
final class Router
{
    /** @var array<int, array{method:string,pattern:string,handler:array,middleware:array}> */
    private array $routes = [];

    public function add(string $method, string $path, array $handler, array $middleware = []): void
    {
        $pattern = preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => '#^' . $pattern . '$#',
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function get(string $p, array $h, array $m = []): void    { $this->add('GET', $p, $h, $m); }
    public function post(string $p, array $h, array $m = []): void   { $this->add('POST', $p, $h, $m); }
    public function put(string $p, array $h, array $m = []): void    { $this->add('PUT', $p, $h, $m); }
    public function delete(string $p, array $h, array $m = []): void { $this->add('DELETE', $p, $h, $m); }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            if (!preg_match($route['pattern'], $request->path, $matches)) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            // Run middleware (each returns true to continue, or halts itself).
            foreach ($route['middleware'] as $mw) {
                (new $mw())->handle($request);
            }

            [$class, $method] = $route['handler'];
            $controller = new $class();
            $controller->$method($request, $params);
            return;
        }

        // No route matched.
        if ($request->wantsJson()) {
            Response::error('Not found', 404);
        }
        http_response_code(404);
        View::render('pages/error', ['code' => 404, 'message' => 'Page introuvable'], 'main');
    }
}
