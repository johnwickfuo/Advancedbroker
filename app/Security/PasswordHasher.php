<?php
declare(strict_types=1);
namespace App\Security;

final class PasswordHasher {
    private static function algorithm(): string|int {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    }
    public static function hash(string $password): string {
        $hash=password_hash($password,self::algorithm());
        if ($hash===false) throw new \RuntimeException('Password hashing failed.');
        return $hash;
    }
    public static function verify(string $password,string $hash): bool { return password_verify($password,$hash); }
    public static function needsRehash(string $hash): bool { return password_needs_rehash($hash,self::algorithm()); }
}
