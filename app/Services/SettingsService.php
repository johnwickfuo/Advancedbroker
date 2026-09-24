<?php
declare(strict_types=1);
namespace App\Services;
use App\Support\{Cache,Database};
final class SettingsService {
    public function __construct(private ?Database $db, private Cache $cache) {}
    public function bool(string $key, bool $default=false): bool { $value=$this->get($key,$default); return is_array($value) ? (bool)($value['enabled']??$default) : (bool)$value; }
    public function get(string $key,mixed $default=null): mixed { if(!$this->db)return $default; return $this->cache->remember('setting.'.$key,60,function()use($key,$default){$raw=$this->db?->scalar('SELECT setting_value FROM settings WHERE setting_key=?',[$key]); if($raw===false||$raw===null)return $default; $decoded=json_decode((string)$raw,true); return json_last_error()===JSON_ERROR_NONE?$decoded:$default;}); }
    public function set(string $key,mixed $value): void { if(!$this->db)throw new \RuntimeException('Database unavailable.'); $this->db->execute('INSERT INTO settings (setting_key,setting_value,is_public,created_at,updated_at) VALUES (?, ?,0,NOW(),NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=NOW()',[$key,json_encode($value,JSON_THROW_ON_ERROR)]); $this->cache->forget('setting.'.$key); }
}
