<?php
declare(strict_types=1);
namespace App\Services;

use App\Support\Database;

final class BrandingService {
    public function __construct(private ?Database $db) {}
    public function forCountry(array $country): array {
        $defaults=['brand_name'=>(string)config('app.name'),'short_name'=>strtoupper((string)config('app.name')),'support_email'=>null,'support_phone'=>null,'support_whatsapp'=>null,'business_address'=>null,'support_hours'=>null,'contact_text'=>null,'footer_text'=>null,'social_links'=>[],'meta_title'=>null,'meta_description'=>null,'logo_asset_id'=>null,'dark_logo_asset_id'=>null,'favicon_asset_id'=>null];
        if(!$this->db)return $defaults;
        $global=$this->db->one('SELECT b.* FROM country_branding b JOIN countries c ON c.id=b.country_id WHERE c.is_global=1 LIMIT 1')??[];
        $local=$this->db->one('SELECT * FROM country_branding WHERE country_id=? LIMIT 1',[(int)$country['id']])??[];
        $merged=$defaults;
        foreach(array_keys($defaults) as $k){$value=$local[$k]??null;if($value===null||$value==='')$value=$global[$k]??null;if($value!==null&&$value!=='')$merged[$k]=$value;}
        if(is_string($merged['social_links'])){$d=json_decode($merged['social_links'],true);$merged['social_links']=is_array($d)?$d:[];}
        return $merged;
    }
    public function save(int $countryId,array $data): void {
        if(!$this->db)throw new \RuntimeException('Database unavailable.');
        $fields=['brand_name','short_name','support_email','support_phone','support_whatsapp','business_address','support_hours','contact_text','footer_text','meta_title','meta_description'];
        $values=[];foreach($fields as $f)$values[]=trim((string)($data[$f]??''))?:null;$values[]=json_encode($data['social_links']??[],JSON_THROW_ON_ERROR);$values[]=$countryId;
        $this->db->execute('INSERT INTO country_branding(brand_name,short_name,support_email,support_phone,support_whatsapp,business_address,support_hours,contact_text,footer_text,meta_title,meta_description,social_links,country_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE brand_name=VALUES(brand_name),short_name=VALUES(short_name),support_email=VALUES(support_email),support_phone=VALUES(support_phone),support_whatsapp=VALUES(support_whatsapp),business_address=VALUES(business_address),support_hours=VALUES(support_hours),contact_text=VALUES(contact_text),footer_text=VALUES(footer_text),meta_title=VALUES(meta_title),meta_description=VALUES(meta_description),social_links=VALUES(social_links),updated_at=NOW()',$values);
    }
    public function attachAsset(int $countryId,string $column,int $assetId): void {
        if(!$this->db||!in_array($column,['logo_asset_id','dark_logo_asset_id','favicon_asset_id'],true))throw new \InvalidArgumentException('Invalid branding asset.');
        $this->db->execute('INSERT INTO country_branding(country_id,'.$column.',created_at,updated_at) VALUES (?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE '.$column.'=VALUES('.$column.'),updated_at=NOW()',[$countryId,$assetId]);
    }
}
