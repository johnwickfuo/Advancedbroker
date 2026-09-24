<?php
declare(strict_types=1);
namespace App\Support;
final readonly class Pagination {
    public function __construct(public int $page, public int $perPage, public int $total) {}
    public function pages(): int { return max(1, (int) ceil($this->total / $this->perPage)); }
    public function offset(): int { return ($this->page - 1) * $this->perPage; }
    public function hasNext(): bool { return $this->page < $this->pages(); }
}
