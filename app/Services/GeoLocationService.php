<?php
declare(strict_types=1);
namespace App\Services;
use App\Data\GeoLocation;
use App\Support\Request;

final class GeoLocationService {
    public function __construct(private readonly array $config) {}
    public function locate(Request $request): GeoLocation {
        $driver = $this->config['driver'] ?? 'auto';
        if (in_array($driver, ['auto','cloudflare'], true)) { $header = strtoupper(trim((string)$request->header('CF-IPCountry', ''))); if ($this->trustedCloudflareRequest($request) && preg_match('/^[A-Z]{2}$/', $header)) return new GeoLocation($header, null, 'cloudflare_header', true); if ($driver === 'cloudflare') return GeoLocation::unknown('cloudflare_untrusted_or_missing'); }
        if (in_array($driver, ['auto','maxmind'], true)) { $location = $this->maxMind($request->ip()); if ($location) return $location; }
        return GeoLocation::unknown($driver === 'maxmind' ? 'maxmind_unavailable' : 'fallback');
    }
    private function trustedCloudflareRequest(Request $request): bool { if (!($this->config['trust_cloudflare_country_header'] ?? false)) return false; $trusted = $this->config['trusted_proxies'] ?? []; return in_array($request->ip(), $trusted, true); }
    private function maxMind(string $ip): ?GeoLocation { $path = (string)($this->config['database_path'] ?? ''); if ($path === '' || !is_file($path) || !class_exists('GeoIp2\\Database\\Reader')) return null; try { $record = (new \GeoIp2\Database\Reader($path))->country($ip); $code = strtoupper((string)($record->country->isoCode ?? '')); return preg_match('/^[A-Z]{2}$/', $code) ? new GeoLocation($code, $record->country->name ?? null, 'maxmind', true) : null; } catch (\Throwable) { return null; } }
}
