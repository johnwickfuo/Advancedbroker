<?php
declare(strict_types=1);
namespace App\Services;
final class TokenService {
    public static function raw(int $bytes=32): string { return rtrim(strtr(base64_encode(random_bytes($bytes)),'+/','-_'),'='); }
    public static function hash(string $token): string { return hash('sha256',$token); }
    public static function uuid(): string { $hex=bin2hex(random_bytes(16)); return sprintf('%s-%s-4%s-%s%s-%s',substr($hex,0,8),substr($hex,8,4),substr($hex,13,3),dechex((hexdec($hex[16])&0x3)|0x8),substr($hex,17,3),substr($hex,20)); }
}
