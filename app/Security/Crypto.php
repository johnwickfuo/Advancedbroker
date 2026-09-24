<?php
declare(strict_types=1);
namespace App\Security;

final class Crypto {
    private string $key;
    public function __construct(string $appKey) {
        if ($appKey==='') throw new \InvalidArgumentException('Encryption key is required.');
        $this->key=hash('sha256',$appKey,true);
    }
    public function encrypt(string $plain): string {
        $iv=random_bytes(12); $tag='';
        $cipher=openssl_encrypt($plain,'aes-256-gcm',$this->key,OPENSSL_RAW_DATA,$iv,$tag);
        if ($cipher===false) throw new \RuntimeException('Encryption failed.');
        return base64_encode($iv.$tag.$cipher);
    }
    public function decrypt(string $payload): string {
        $raw=base64_decode($payload,true);
        if ($raw===false || strlen($raw)<29) throw new \RuntimeException('Invalid encrypted payload.');
        $iv=substr($raw,0,12); $tag=substr($raw,12,16); $cipher=substr($raw,28);
        $plain=openssl_decrypt($cipher,'aes-256-gcm',$this->key,OPENSSL_RAW_DATA,$iv,$tag);
        if ($plain===false) throw new \RuntimeException('Decryption failed.');
        return $plain;
    }
}
