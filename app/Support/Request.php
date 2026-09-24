<?php
declare(strict_types=1);
namespace App\Support;
final class Request {
    private array $routeParameters = [];
    public function __construct(public readonly string $method, public readonly string $path, private readonly array $query, private readonly array $body, private readonly array $files, private readonly array $server) {}
    public static function capture(): self { $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; $method = strtoupper($_POST['_method'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET'); return new self($method, '/' . trim($uri, '/'), $_GET, $_POST, $_FILES, $_SERVER); }
    public function input(string $key, mixed $default = null): mixed { return $this->body[$key] ?? $this->query[$key] ?? $default; }
    public function all(): array { return array_merge($this->query, $this->body); }
    public function file(string $key): ?array { return $this->files[$key] ?? null; }
    public function files(): array { return $this->files; }
    public function ip(): string { return $this->server['REMOTE_ADDR'] ?? '0.0.0.0'; }
    public function header(string $name, mixed $default = null): mixed { $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name)); return $this->server[$key] ?? $default; }
    public function userAgent(): string { return $this->server['HTTP_USER_AGENT'] ?? ''; }
    public function setRouteParameters(array $parameters): void { $this->routeParameters = $parameters; }
    public function route(string $key, mixed $default = null): mixed { return $this->routeParameters[$key] ?? $default; }
    public function isAjax(): bool { return strtolower($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'; }
}
