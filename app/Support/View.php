<?php
declare(strict_types=1);
namespace App\Support;
final class View {
    public function __construct(private readonly string $path) {}
    public function render(string $view, array $data = [], string $layout = 'layouts.public'): string {
        $content = $this->file($view, $data);
        $html = $this->file($layout, array_merge($data, ['content' => $content]));

        if (in_array($layout, ['layouts.public','layouts.auth'], true)) {
            try {
                $context = country();
                if ($context->languageCode !== 'en') {
                    $html = app('page_translation')->translateHtml($html, $context->languageCode);
                }
            } catch (\Throwable $e) {
                try { app('logger')->error('Page translation failed; serving source language.', ['message'=>$e->getMessage()]); } catch (\Throwable) {}
            }
        }

        return $html;
    }
    public function partial(string $view, array $data = []): string { return $this->file($view, $data); }
    private function file(string $view, array $data): string { $file = $this->path . '/' . str_replace('.', '/', $view) . '.php'; if (!is_file($file)) throw new \RuntimeException("View [$view] does not exist."); extract($data, EXTR_SKIP); ob_start(); require $file; return (string) ob_get_clean(); }
}
