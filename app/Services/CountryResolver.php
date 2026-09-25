<?php
declare(strict_types=1);
namespace App\Services;
use App\Data\CountryContext;
use App\Data\GeoLocation;
use App\Support\Request;

final class CountryResolver {
    public function __construct(private readonly CountryService $countries, private readonly GeoLocationService $geo, private readonly bool $debug = false) {}

    /** @param array{id?:int,assigned_country_id?:int|null,country_id?:int|null,preferred_language_code?:string|null}|null $account */
    public function resolve(Request $request, ?array $account = null, ?int $browserCountryId = null): CountryContext {
        $notRequired=GeoLocation::unknown('country_locked_no_geoip_required');

        if (($_SESSION['user_role'] ?? '') === 'super_admin' && !empty($_SESSION['admin_country_preview']['country_id'])) {
            $preview=$this->countries->byId((int)$_SESSION['admin_country_preview']['country_id']);
            if($preview)return new CountryContext($preview,(string)($_SESSION['admin_country_preview']['language']?:$this->countries->defaultLanguage($preview)),$notRequired,'admin_preview');
        }

        // Once an account exists, its stored/admin-assigned country is always
        // authoritative. IP changes, VPNs and travel never move the account.
        if ($account && !empty($account['id'])) {
            foreach (['assigned_country_id' => 'admin_assignment', 'country_id' => 'stored_account_country'] as $field => $source) {
                $id=(int)($account[$field]??0);
                if($id && ($country=$this->countries->byId($id))) return new CountryContext($country,$this->countries->defaultLanguage($country),$notRequired,$source);
            }
            return $this->global($notRequired,'authenticated_global_fallback');
        }

        // A browser market lock exists only after successful account
        // association. It intentionally takes priority over later IP changes.
        if ($browserCountryId) {
            $locked=$this->countries->byId($browserCountryId);
            if($locked && (!empty($locked['is_global']) || (!empty($locked['is_active']) && !empty($locked['is_enabled'])))){
                return new CountryContext($locked,$this->countries->defaultLanguage($locked),$notRequired,'browser_account_lock');
            }
        }

        if ($this->debug && ($preview=(string)$request->input('preview_country',$_SESSION['preview_country']??''))) {
            $country=$this->countries->bySlug($preview)??$this->countries->byCode($preview);
            if($country){$_SESSION['preview_country']=$preview;return new CountryContext($country,$this->countries->defaultLanguage($country),$notRequired,'development_preview');}
        }

        // Truly anonymous, unlocked browsers are resolved on every request.
        // Nothing about this country is persisted to the browser/session.
        $geo = $this->geo->locate($request);
        if ($geo->succeeded && $geo->countryCode && ($country=$this->countries->enabledByCode($geo->countryCode))) {
            return new CountryContext($country,$this->countries->defaultLanguage($country),$geo,'geoip');
        }

        return $this->global($geo,$geo->succeeded?'unsupported_geoip_global_fallback':'geoip_failure_global_fallback');
    }

    private function global(GeoLocation $geo,string $source):CountryContext{
        $country=$this->countries->global();
        return new CountryContext($country,$this->countries->defaultLanguage($country),$geo,$source);
    }
}
