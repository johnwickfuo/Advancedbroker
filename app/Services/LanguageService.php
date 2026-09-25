<?php
declare(strict_types=1);
namespace App\Services;
use App\Data\CountryContext;
use App\Support\Request;

final class LanguageService {
    public function __construct(private readonly CountryService $countries) {}

    public function resolve(CountryContext $context, Request $request, ?array $account = null): CountryContext {
        // Authenticated users keep their saved language preference as long as
        // that language is valid for their locked account market.
        if ($account) {
            $preferred = $account['preferred_language_code'] ?? null;
            $language = is_string($preferred) && $this->countries->supportsLanguage($context->country, $preferred)
                ? $preferred
                : $this->countries->defaultLanguage($context->country);

            $_SESSION['language_code'] = $language;
            $_SESSION['language_country_id'] = $context->id();
            return $context->withLanguage($language);
        }

        // Guests can move between markets as their IP changes. A language
        // preference is only reused while it belongs to the same market.
        $sameCountry = (int)($_SESSION['language_country_id'] ?? 0) === $context->id();
        $preferred = $sameCountry ? ($_SESSION['language_code'] ?? null) : null;

        $language = is_string($preferred) && $this->countries->supportsLanguage($context->country, $preferred)
            ? $preferred
            : $this->countries->defaultLanguage($context->country);

        $_SESSION['language_code'] = $language;
        $_SESSION['language_country_id'] = $context->id();

        return $context->withLanguage($language);
    }

    public function select(CountryContext $context, string $language): bool {
        if (!$this->countries->supportsLanguage($context->country, $language)) return false;
        $_SESSION['language_code'] = $language;
        $_SESSION['language_country_id'] = $context->id();
        return true;
    }
}
