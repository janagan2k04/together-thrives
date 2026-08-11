<?php
namespace Core;

class Router
{
    private array $routes = [];
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    public function get(string $p, string $h, array $mw = []): void    { $this->add('GET', $p, $h, $mw); }
    public function post(string $p, string $h, array $mw = []): void   { $this->add('POST', $p, $h, $mw); }
    public function put(string $p, string $h, array $mw = []): void    { $this->add('PUT', $p, $h, $mw); }
    public function delete(string $p, string $h, array $mw = []): void { $this->add('DELETE', $p, $h, $mw); }

    /**
     * Group routes under a shared prefix and middleware stack.
     *   $r->group('/admin', ['auth','role:admin'], function($r) { ... });
     */
    public function group(string $prefix, array $middleware, callable $fn): void
    {
        $prevPrefix = $this->groupPrefix;
        $prevMw     = $this->groupMiddleware;

        $this->groupPrefix     = $prevPrefix . $prefix;
        $this->groupMiddleware = array_merge($prevMw, $middleware);

        $fn($this);

        $this->groupPrefix     = $prevPrefix;
        $this->groupMiddleware = $prevMw;
    }

    private function add(string $method, string $path, string $handler, array $mw): void
    {
        $path = $this->groupPrefix . $path;
        $path = '/' . trim($path, '/');
        if ($path === '/') $path = '/';

        $this->routes[$method][] = [
            'regex'      => $this->toRegex($path),
            'params'     => $this->paramNames($path),
            'handler'    => $handler,
            'middleware' => array_merge($this->groupMiddleware, $mw),
            'path'       => $path,
        ];
    }

    /** /cleanup/{id}/edit  ->  #^/cleanup/([^/]+)/edit$# */
    private function toRegex(string $path): string
    {
        $quoted = preg_quote($path, '#');
        $regex  = preg_replace('#\\\{[a-zA-Z_]+\\\}#', '([^/]+)', $quoted);
        return '#^' . $regex . '$#';
    }

    private function paramNames(string $path): array
    {
        preg_match_all('#\{([a-zA-Z_]+)\}#', $path, $m);
        return $m[1];
    }

    public function dispatch(string $uri, string $method): void
    {
        foreach ($this->routes[$method] ?? [] as $route) {
            if (!preg_match($route['regex'], $uri, $matches)) continue;

            array_shift($matches);
            $params = array_combine($route['params'], $matches) ?: [];

            foreach ($route['middleware'] as $mw) {
                MiddlewareRunner::run($mw);
            }

            [$class, $action] = explode('@', $route['handler']);
            $fqcn = 'Controllers\\' . $class;

            if (!class_exists($fqcn)) {
                throw new HttpException(500, "Controller {$fqcn} not found.");
            }
            $controller = new $fqcn();
            if (!method_exists($controller, $action)) {
                throw new HttpException(500, "Method {$action} missing on {$fqcn}.");
            }

            $controller->$action(...array_values($params));
            return;
        }

        throw new HttpException(404, 'Page not found.');
    }
}
