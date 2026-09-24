<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Contracts\CountryRepository;
use App\Support\Database;

final class DatabaseCountryRepository implements CountryRepository {
    public function __construct(private Database $db) {}
    public function findById(int $id): ?array { return $this->normalize($this->db->one('SELECT * FROM countries WHERE id=? LIMIT 1',[$id])); }
    public function findByCode(string $code): ?array { return $this->normalize($this->db->one('SELECT * FROM countries WHERE code=? LIMIT 1',[strtoupper($code)])); }
    public function findBySlug(string $slug): ?array { return $this->normalize($this->db->one('SELECT * FROM countries WHERE slug=? LIMIT 1',[strtolower($slug)])); }
    public function global(): ?array { return $this->normalize($this->db->one('SELECT * FROM countries WHERE is_global=1 LIMIT 1')); }
    public function allEnabled(): array { return array_map([$this,'normalizeRow'],$this->db->select('SELECT * FROM countries WHERE is_global=1 OR (is_active=1 AND is_enabled=1) ORDER BY sort_order,name')); }
    public function all(): array { return array_map([$this,'normalizeRow'],$this->db->select('SELECT * FROM countries ORDER BY sort_order,name')); }
    public function languagesFor(int $countryId): array {
        return $this->db->select('SELECT l.code,l.locale,l.name,l.native_name,cl.is_default FROM country_languages cl JOIN languages l ON l.id=cl.language_id WHERE cl.country_id=? AND l.active=1 ORDER BY cl.is_default DESC,l.name',[$countryId]);
    }
    public function contentFor(int $countryId,string $languageCode,string $page): ?array {
        $raw=$this->db->scalar('SELECT c.content FROM country_page_contents c JOIN languages l ON l.id=c.language_id WHERE c.country_id=? AND l.code=? AND c.page_key=? LIMIT 1',[$countryId,$languageCode,$page]);
        if($raw===false||$raw===null) return null;
        $decoded=json_decode((string)$raw,true);
        return is_array($decoded)?$decoded:null;
    }
    private function normalize(?array $row): ?array { return $row?$this->normalizeRow($row):null; }
    public function normalizeRow(array $row): array {
        foreach(['theme','visual_assets'] as $field) if(isset($row[$field])&&is_string($row[$field])) { $decoded=json_decode($row[$field],true); $row[$field]=is_array($decoded)?$decoded:[]; }
        return $row;
    }
}
