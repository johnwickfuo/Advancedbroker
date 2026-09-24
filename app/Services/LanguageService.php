<?php
declare(strict_types=1);
namespace App\Services;
use App\Data\CountryContext;
use App\Support\Request;

final class LanguageService {
    public function __construct(private readonly CountryService $countries) {}
    public function resolve(CountryContext $context, Request $request, ?array $account = null): CountryContext { $preferred = $account['preferred_language_code'] ?? $_SESSION['language_code'] ?? null; $language = is_string($preferred) && $this->countries->supportsLanguage($context->country, $preferred) ? $preferred : $this->countries->defaultLanguage($context->country); $_SESSION['language_code'] = $language; return $context->withLanguage($language); }
    public function select(CountryContext $context, string $language): bool { if (!$this->countries->supportsLanguage($context->country, $language)) return false; $_SESSION['language_code'] = $language; return true; }
}
