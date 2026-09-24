<?php
declare(strict_types=1);
namespace App\Services;

use App\Support\Database;

final class ContentService {
    public function __construct(private ?Database $db) {}
    public function page(array $country,string $language,string $page): array {
        if(!$this->db)return [];
        $codes=array_values(array_unique([$language,app('countries')->defaultLanguage($country),'en']));
        foreach($codes as $code){$raw=$this->db->scalar('SELECT p.content FROM country_page_contents p JOIN languages l ON l.id=p.language_id WHERE p.country_id=? AND l.code=? AND p.page_key=? LIMIT 1',[$country['id'],$code,$page]);if($raw!==false&&$raw!==null){$d=json_decode((string)$raw,true);if(is_array($d))return$d;}}
        return [];
    }
    public function save(int $countryId,int $languageId,string $page,array $data): void {
        if(!$this->db)throw new \RuntimeException('Database unavailable.');
        $content=$data;foreach(['_token','language_id','page_key'] as $k)unset($content[$k]);
        $this->db->execute('INSERT INTO country_page_contents(country_id,language_id,page_key,content,created_at,updated_at) VALUES (?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE content=VALUES(content),updated_at=NOW()',[$countryId,$languageId,$page,json_encode($content,JSON_THROW_ON_ERROR)]);
    }
}
