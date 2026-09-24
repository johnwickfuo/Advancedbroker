<?php
declare(strict_types=1);
namespace App\Data;

final readonly class CountryContext {
    public function __construct(
        public array $country,
        public string $languageCode,
        public GeoLocation $geo,
        public string $resolutionSource
    ) {}
    public function withLanguage(string $language): self { return new self($this->country,$language,$this->geo,$this->resolutionSource); }
    public function id(): int { return (int)($this->country['id'] ?? 0); }
    public function name(): string { return (string)($this->country['name'] ?? 'Global'); }
    public function isGlobal(): bool { return (bool)($this->country['is_global'] ?? false); }
    public function currencyCode(): string { return (string)($this->country['currency_code'] ?? 'USD'); }
    public function currencySymbol(): string { return (string)($this->country['currency_symbol'] ?? '$'); }
    public function locale(): string { return (string)($this->country['locale'] ?? 'en'); }
    public function timezone(): string { return (string)($this->country['timezone'] ?? 'UTC'); }
    public function theme(): array {
        $raw=$this->country['theme'] ?? [];
        if (is_string($raw)) { $decoded=json_decode($raw,true); return is_array($decoded)?$decoded:[]; }
        return is_array($raw)?$raw:[];
    }
}
