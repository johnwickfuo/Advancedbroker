<?php
declare(strict_types=1);
namespace App\Services;
use App\Contracts\CountryRepository;
use App\Support\Cache;

final class CountryService {
    public function __construct(private readonly CountryRepository $repository, private readonly ?Cache $cache = null) {}
    public function global(): array { return $this->remember('global', fn() => $this->repository->global() ?? throw new \RuntimeException('Global Country Pack is required.')); }
    public function allEnabled(): array { return $this->remember('all-enabled', fn()=>$this->repository->allEnabled()); }
    public function all(): array { return $this->remember('all', fn()=>$this->repository->all()); }
    public function byId(int $id): ?array { return $this->remember("id:$id", fn() => $this->repository->findById($id)); }
    public function byCode(string $code): ?array { $code = strtoupper($code); return $this->remember("code:$code", fn() => $this->repository->findByCode($code)); }
    public function bySlug(string $slug): ?array { $slug = strtolower($slug); return $this->remember("slug:$slug", fn() => $this->repository->findBySlug($slug)); }
    public function enabledById(int $id): ?array { $country = $this->byId($id); return $this->isEnabled($country) ? $country : null; }
    public function enabledByCode(string $code): ?array { $country = $this->byCode($code); return $this->isEnabled($country) ? $country : null; }
    public function isEnabled(?array $country): bool { return $country !== null && ((bool)($country['is_global'] ?? false) || ((bool)($country['is_enabled'] ?? $country['is_active'] ?? false) && (bool)($country['is_active'] ?? true))); }
    public function languages(array $country): array { return $this->remember('languages:' . $country['id'], fn() => $this->repository->languagesFor((int)$country['id'])); }
    public function supportsLanguage(array $country, string $code): bool { foreach ($this->languages($country) as $language) if ($language['code'] === $code) return true; return false; }
    public function defaultLanguage(array $country): string { foreach ($this->languages($country) as $language) if ((bool)$language['is_default']) return (string)$language['code']; return 'en'; }
    public function homepageContent(array $country, string $language): array { return $this->repository->contentFor((int)$country['id'], $language, 'home') ?? $this->repository->contentFor((int)$country['id'], $this->defaultLanguage($country), 'home') ?? $this->repository->contentFor((int)$country['id'], 'en', 'home') ?? []; }
    public function forget(array $country): void { if (!$this->cache) return; foreach (['id:' . $country['id'], 'code:' . ($country['code'] ?? ''), 'slug:' . $country['slug'], 'languages:' . $country['id'], 'global','all','all-enabled'] as $key) $this->cache->forget('country:' . $key); }
    private function remember(string $key, callable $resolver): mixed { if (!$this->cache) return $resolver(); $cacheKey = 'country:' . $key; $sentinel = new \stdClass(); $value = $this->cache->get($cacheKey, $sentinel); if ($value !== $sentinel) return $value; $value = $resolver(); if ($value !== null) $this->cache->put($cacheKey, $value, 300); return $value; }
}
