<?php
declare(strict_types=1);
namespace App\Services;

final class Translator {
    private array $loaded = [];
    public function __construct(private readonly string $path, private readonly array $allowedLanguages) {}
    public function get(string $key, string $language, string $countryDefault = 'en', array $replace = []): string {
        foreach (array_unique([$language, $countryDefault, 'en']) as $candidate) { $value = $this->find($key, $candidate); if ($value !== null) return $this->replace($value, $replace); }
        return $this->replace('Text unavailable', $replace);
    }
    private function find(string $key, string $language): ?string { if (!in_array($language, $this->allowedLanguages, true)) return null; $catalog = $this->load($language); $value = $catalog; foreach (explode('.', $key) as $part) { if (!is_array($value) || !array_key_exists($part, $value)) return null; $value = $value[$part]; } return is_string($value) ? $value : null; }
    private function load(string $language): array { if (isset($this->loaded[$language])) return $this->loaded[$language]; $file = $this->path . '/' . $language . '/messages.php'; return $this->loaded[$language] = is_file($file) ? (require $file) : []; }
    private function replace(string $text, array $replace): string { foreach ($replace as $key => $value) $text = str_replace(':' . $key, (string)$value, $text); return $text; }
}
