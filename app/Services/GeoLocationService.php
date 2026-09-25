<?php
declare(strict_types=1);
namespace App\Services;

use App\Data\GeoLocation;
use App\Support\{Cache,Request};

final class GeoLocationService {
    public function __construct(private readonly array $config, private readonly ?Cache $cache = null) {}

    public function locate(Request $request): GeoLocation {
        $driver = strtolower((string)($this->config['driver'] ?? 'auto'));

        if (in_array($driver, ['auto','cloudflare'], true)) {
            $header = strtoupper(trim((string)$request->header('CF-IPCountry', '')));
            if ($this->trustedCloudflareRequest($request) && preg_match('/^[A-Z]{2}$/', $header) && !in_array($header,['XX','T1'],true)) {
                return new GeoLocation($header, null, 'cloudflare_header', true);
            }
            if ($driver === 'cloudflare') return GeoLocation::unknown('cloudflare_untrusted_or_missing');
        }

        if (in_array($driver, ['auto','maxmind'], true)) {
            $location = $this->maxMind($this->clientIp($request));
            if ($location) return $location;
            if ($driver === 'maxmind' && !($this->config['remote_fallback'] ?? false)) {
                return GeoLocation::unknown('maxmind_unavailable');
            }
        }

        if (($driver === 'auto' || $driver === 'ipwhois' || ($this->config['remote_fallback'] ?? false))) {
            $location = $this->remote($this->clientIp($request));
            if ($location) return $location;
        }

        return GeoLocation::unknown('geoip_unavailable');
    }

    private function clientIp(Request $request): string {
        if ($this->trustedCloudflareRequest($request)) {
            $forwarded=trim((string)$request->header('CF-Connecting-IP',''));
            if (filter_var($forwarded,FILTER_VALIDATE_IP)) return $forwarded;
        }
        return $request->ip();
    }

    private function trustedCloudflareRequest(Request $request): bool {
        if (!($this->config['trust_cloudflare_country_header'] ?? false)) return false;
        $trusted = $this->config['trusted_proxies'] ?? [];
        return in_array($request->ip(), $trusted, true);
    }

    private function maxMind(string $ip): ?GeoLocation {
        $path = (string)($this->config['database_path'] ?? '');
        if ($path === '' || !is_file($path) || !class_exists('GeoIp2\\Database\\Reader')) return null;
        try {
            $record = (new \GeoIp2\Database\Reader($path))->country($ip);
            $code = strtoupper((string)($record->country->isoCode ?? ''));
            return preg_match('/^[A-Z]{2}$/', $code) ? new GeoLocation($code, $record->country->name ?? null, 'maxmind', true) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function remote(string $ip): ?GeoLocation {
        if (!($this->config['remote_fallback'] ?? true)) return null;
        if (!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)) return null;

        $cacheKey='geoip:remote:'.hash('sha256',$ip);
        if($this->cache){
            $cached=$this->cache->get($cacheKey);
            if(is_array($cached) && isset($cached['code'])){
                return new GeoLocation((string)$cached['code'],$cached['name']??null,'ipwhois_cache',true);
            }
        }

        $timeout=max(1,min(5,(int)($this->config['remote_timeout']??2)));
        $url='https://ipwho.is/'.rawurlencode($ip);
        $raw=$this->httpGet($url,$timeout);
        if($raw===null)return null;

        $data=json_decode($raw,true);
        if(!is_array($data)||empty($data['success']))return null;
        $code=strtoupper((string)($data['country_code']??''));
        if(!preg_match('/^[A-Z]{2}$/',$code))return null;

        $name=isset($data['country'])?(string)$data['country']:null;
        if($this->cache){
            $ttl=max(300,min(86400,(int)($this->config['cache_seconds']??21600)));
            $this->cache->put($cacheKey,['code'=>$code,'name'=>$name],$ttl);
        }

        return new GeoLocation($code,$name,'ipwhois',true);
    }

    private function httpGet(string $url,int $timeout): ?string {
        if(function_exists('curl_init')){
            $ch=curl_init($url);
            if($ch!==false){
                curl_setopt_array($ch,[
                    CURLOPT_RETURNTRANSFER=>true,
                    CURLOPT_FOLLOWLOCATION=>false,
                    CURLOPT_CONNECTTIMEOUT=>$timeout,
                    CURLOPT_TIMEOUT=>$timeout,
                    CURLOPT_USERAGENT=>'UpgradedBroker-GeoIP/1.0',
                    CURLOPT_HTTPHEADER=>['Accept: application/json'],
                ]);
                $body=curl_exec($ch);
                $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
                curl_close($ch);
                if(is_string($body)&&$status>=200&&$status<300)return $body;
            }
        }

        if((bool)ini_get('allow_url_fopen')){
            $context=stream_context_create(['http'=>[
                'method'=>'GET',
                'timeout'=>$timeout,
                'ignore_errors'=>true,
                'header'=>"Accept: application/json\r\nUser-Agent: UpgradedBroker-GeoIP/1.0\r\n",
            ]]);
            $body=@file_get_contents($url,false,$context);
            return is_string($body)?$body:null;
        }

        return null;
    }
}
