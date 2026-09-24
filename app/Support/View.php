<?php
declare(strict_types=1);
namespace App\Support;
final class View {
    public function __construct(private readonly string $path) {}
    public function render(string $view, array $data = [], string $layout = 'layouts.public'): string { $content = $this->file($view, $data); return $this->file($layout, array_merge($data, ['content' => $content])); }
    public function partial(string $view, array $data = []): string { return $this->file($view, $data); }
    private function file(string $view, array $data): string { $file = $this->path . '/' . str_replace('.', '/', $view) . '.php'; if (!is_file($file)) throw new \RuntimeException("View [$view] does not exist."); extract($data, EXTR_SKIP); ob_start(); require $file; return (string) ob_get_clean(); }
}
