<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Persists a market choice only after a browser has been associated with an
 * authenticated account. Unsigned browsers receive no country cookie and are
 * therefore resolved from their current request IP on every request.
 */
final class BrowserMarketLockService
{
    private const COOKIE = 'upgradedbroker_market';
    private const LIFETIME = 31536000; // one year

    public function __construct(private readonly string $appKey) {}

    public function countryId(): ?int
    {
        $raw = (string)($_COOKIE[self::COOKIE] ?? '');
        if ($raw === '') return null;

        [$id,$expires,$signature] = array_pad(explode('.', $raw, 3), 3, '');
        if (!ctype_digit($id) || !ctype_digit($expires) || $signature === '') return null;
        if ((int)$expires < time()) return null;

        $payload = $id . '|' . $expires;
        $expected = hash_hmac('sha256', $payload, $this->signingKey());
        if (!hash_equals($expected, $signature)) return null;

        $countryId = (int)$id;
        return $countryId > 0 ? $countryId : null;
    }

    public function lock(int $countryId): void
    {
        if ($countryId < 1 || headers_sent()) return;

        $expires = time() + self::LIFETIME;
        $payload = $countryId . '|' . $expires;
        $value = $countryId . '.' . $expires . '.' . hash_hmac('sha256', $payload, $this->signingKey());

        setcookie(self::COOKIE, $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => \App\Security\Session::cookieShouldBeSecure((array)config('app.session',[])),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // Make it available during the current PHP request too.
        $_COOKIE[self::COOKIE] = $value;
    }

    public function clear(): void
    {
        setcookie(self::COOKIE, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => \App\Security\Session::cookieShouldBeSecure((array)config('app.session',[])),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[self::COOKIE]);
    }

    private function signingKey(): string
    {
        return hash('sha256', 'browser-market-lock|' . $this->appKey, true);
    }
}
