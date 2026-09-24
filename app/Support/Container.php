<?php
declare(strict_types=1);
namespace App\Support;

final class Container {
    private static ?self $instance = null; private array $items = [];
    public static function instance(): self { return self::$instance ??= new self(); }
    public function set(string $id, mixed $item): void { $this->items[$id] = $item; }
    public function get(string $id): mixed { if (!array_key_exists($id, $this->items)) throw new \RuntimeException("Service [$id] is not registered."); return $this->items[$id]; }
}
