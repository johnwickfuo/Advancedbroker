<?php
declare(strict_types=1);
namespace App\Support;
use App\Exceptions\NotFoundException;

final class Router {
    private array $routes = [], $named = [], $groupStack = [];
    public function get(string $uri, callable|array $action, string $name = ''): self { return $this->add(['GET'], $uri, $action, $name); }
    public function post(string $uri, callable|array $action, string $name = ''): self { return $this->add(['POST'], $uri, $action, $name); }
    public function match(array $methods, string $uri, callable|array $action, string $name = ''): self { return $this->add($methods, $uri, $action, $name); }
    public function add(array $methods, string $uri, callable|array $action, string $name = ''): self { $group = end($this->groupStack) ?: ['prefix' => '', 'middleware' => [], 'name' => '']; $route = ['methods' => $methods, 'uri' => '/' . trim($group['prefix'] . '/' . trim($uri, '/'), '/'), 'action' => $action, 'name' => $group['name'] . $name, 'middleware' => $group['middleware']]; $this->routes[] = $route; if ($route['name']) $this->named[$route['name']] = $route; return $this; }
    public function group(array $options, callable $callback): void { $parent = end($this->groupStack) ?: ['prefix' => '', 'middleware' => [], 'name' => '']; $this->groupStack[] = ['prefix' => trim($parent['prefix'] . '/' . ($options['prefix'] ?? ''), '/'), 'middleware' => array_merge($parent['middleware'], $options['middleware'] ?? []), 'name' => $parent['name'] . ($options['as'] ?? '')]; $callback($this); array_pop($this->groupStack); }
    public function dispatch(Request $request): Response { foreach ($this->routes as $route) { if (!in_array($request->method, $route['methods'], true)) continue; $pattern = preg_replace('#\{([A-Za-z_][A-Za-z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['uri']); if (!preg_match('#^' . $pattern . '$#', $request->path, $matches)) continue; $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY); $request->setRouteParameters($params); $core = function (Request $req) use ($route): Response { $result = is_array($route['action']) ? app($route['action'][0])->{$route['action'][1]}($req) : ($route['action'])($req); return $result instanceof Response ? $result : new Response((string) $result); }; $pipeline = array_reduce(array_reverse($route['middleware']), fn($next, $middleware) => fn(Request $req) => app($middleware)->handle($req, $next), $core); return $pipeline($request); } throw new NotFoundException('Page not found.'); }
    public function url(string $name, array $parameters = []): string { if (!isset($this->named[$name])) throw new \InvalidArgumentException("Unknown route [$name]."); $uri = preg_replace_callback('/\{(\w+)\}/', fn($m) => rawurlencode((string)($parameters[$m[1]] ?? throw new \InvalidArgumentException("Missing parameter [{$m[1]}]."))), $this->named[$name]['uri']); return url($uri); }
    public function routes(): array { return $this->routes; }
}
