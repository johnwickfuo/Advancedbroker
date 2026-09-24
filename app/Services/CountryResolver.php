<?php
declare(strict_types=1);
namespace App\Services;
use App\Data\CountryContext;
use App\Data\GeoLocation;
use App\Support\Request;

final class CountryResolver {
    public function __construct(private readonly CountryService $countries, private readonly GeoLocationService $geo, private readonly bool $debug = false) {}
    /** @param array{id?:int,assigned_country_id?:int|null,country_id?:int|null,preferred_language_code?:string|null}|null $account */
    public function resolve(Request $request, ?array $account = null): CountryContext {
        $geo = $this->geo->locate($request);
        if (($_SESSION['user_role'] ?? '') === 'super_admin' && !empty($_SESSION['admin_country_preview']['country_id'])) { $preview=$this->countries->byId((int)$_SESSION['admin_country_preview']['country_id']); if($preview)return new CountryContext($preview,(string)($_SESSION['admin_country_preview']['language']?:$this->countries->defaultLanguage($preview)),$geo,'admin_preview'); }
        if ($account && !empty($account['id'])) {
            foreach (['assigned_country_id' => 'admin_assignment', 'country_id' => 'stored_account_country'] as $field => $source) { $id = (int)($account[$field] ?? 0); if ($id && ($country = $this->countries->enabledById($id))) return new CountryContext($country, $this->countries->defaultLanguage($country), $geo, $source); }
            return $this->global($geo, 'authenticated_global_fallback');
        }
        if ($this->debug && ($preview = (string)$request->input('preview_country', $_SESSION['preview_country'] ?? ''))) { $country = $this->countries->bySlug($preview) ?? $this->countries->byCode($preview); if ($country) { $_SESSION['preview_country'] = $preview; return new CountryContext($country, $this->countries->defaultLanguage($country), $geo, 'development_preview'); } }
        if ($geo->succeeded && $geo->countryCode && ($country = $this->countries->enabledByCode($geo->countryCode))) return new CountryContext($country, $this->countries->defaultLanguage($country), $geo, 'geoip');
        return $this->global($geo, $geo->succeeded ? 'unsupported_geoip_global_fallback' : 'geoip_failure_global_fallback');
    }
    private function global(GeoLocation $geo, string $source): CountryContext { $country = $this->countries->global(); return new CountryContext($country, $this->countries->defaultLanguage($country), $geo, $source); }
}
