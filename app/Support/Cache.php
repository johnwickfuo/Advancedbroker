<?php
declare(strict_types=1);
namespace App\Support;

final class Cache {
    public function __construct(private readonly string $directory) {}
    public function get(string $key, mixed $default = null): mixed { $path = $this->path($key); if (!is_file($path)) return $default; $item = unserialize((string) file_get_contents($path), ['allowed_classes' => false]); if (!is_array($item) || $item['expires'] < time()) { @unlink($path); return $default; } return $item['value']; }
    public function put(string $key, mixed $value, int $seconds = 3600): void { if (!is_dir($this->directory)) mkdir($this->directory, 0750, true); file_put_contents($this->path($key), serialize(['expires' => time()+$seconds, 'value' => $value]), LOCK_EX); }
    public function forget(string $key): void { @unlink($this->path($key)); }
    private function path(string $key): string { return $this->directory . '/' . hash('sha256', $key) . '.cache'; }
}
