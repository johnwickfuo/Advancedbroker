<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Support\{Database,Pagination};

final class CompanyRepository {
    public function __construct(private ?Database $db) {}
    public function catalogue(int $countryId,array $filters,Pagination $pagination): array {
        if(!$this->db)return ['items'=>[],'total'=>0];
        [$where,$params]=$this->publicWhere($countryId,$filters);
        $sort=match($filters['sort']??'featured'){'newest'=>'c.created_at DESC','name'=>'c.display_name ASC','price_low'=>'o.share_price_minor ASC','price_high'=>'o.share_price_minor DESC','projected_return'=>'o.projected_profit_value DESC','duration'=>'o.duration_value ASC',default=>'o.is_featured DESC,c.is_featured DESC,c.sort_order ASC,c.display_name ASC'};
        $base=' FROM companies c JOIN investment_offerings o ON o.company_id=c.id JOIN countries cp ON cp.id=o.country_id WHERE '.implode(' AND ',$where);
        $total=(int)$this->db->scalar('SELECT COUNT(*)'.$base,$params);
        $items=$this->db->select('SELECT c.*,o.id offering_id,o.public_id offering_public_id,o.country_id,o.currency_code,o.share_price_minor,o.minimum_purchase_quantity,o.maximum_purchase_quantity,o.maximum_user_holding,o.total_available_quantity,o.fractional_shares_allowed,o.quantity_decimal_places,o.profit_type,o.projected_profit_value,o.fixed_profit_basis,o.duration_value,o.duration_unit,o.selling_enabled,o.is_active offering_active,o.is_featured offering_featured,o.available_from,o.available_until'.$base.' ORDER BY '.$sort.' LIMIT '.(int)$pagination->offset().','.(int)$pagination->perPage,$params);
        return ['items'=>$items,'total'=>$total];
    }
    public function publicCompany(int $countryId,string $identifier): ?array {
        if(!$this->db)return null;
        return $this->db->one('SELECT c.*,o.id offering_id,o.public_id offering_public_id,o.country_id,o.currency_code,o.share_price_minor,o.minimum_purchase_quantity,o.maximum_purchase_quantity,o.maximum_user_holding,o.total_available_quantity,o.fractional_shares_allowed,o.quantity_decimal_places,o.profit_type,o.projected_profit_value,o.fixed_profit_basis,o.duration_value,o.duration_unit,o.selling_enabled,o.is_active offering_active,o.is_featured offering_featured,o.available_from,o.available_until FROM companies c JOIN investment_offerings o ON o.company_id=c.id WHERE o.country_id=? AND c.status="ACTIVE" AND o.is_active=1 AND (c.public_id=? OR c.slug=?) AND (o.available_from IS NULL OR o.available_from<=NOW()) AND (o.available_until IS NULL OR o.available_until>=NOW()) LIMIT 1',[$countryId,$identifier,$identifier]);
    }
    public function featured(int $countryId,int $limit=6): array {
        if(!$this->db)return [];
        return $this->db->select('SELECT c.*,o.id offering_id,o.currency_code,o.share_price_minor,o.profit_type,o.projected_profit_value,o.duration_value,o.duration_unit FROM companies c JOIN investment_offerings o ON o.company_id=c.id WHERE o.country_id=? AND c.status="ACTIVE" AND o.is_active=1 AND (o.is_featured=1 OR c.is_featured=1) AND (o.available_from IS NULL OR o.available_from<=NOW()) AND (o.available_until IS NULL OR o.available_until>=NOW()) ORDER BY o.is_featured DESC,c.is_featured DESC,c.sort_order,c.display_name LIMIT '.max(1,min($limit,12)),[$countryId]);
    }
    public function industries(int $countryId): array { return $this->db?->select('SELECT DISTINCT c.industry FROM companies c JOIN investment_offerings o ON o.company_id=c.id WHERE o.country_id=? AND c.status="ACTIVE" AND o.is_active=1 AND c.industry IS NOT NULL AND c.industry<>"" ORDER BY c.industry',[$countryId])??[]; }
    public function exchanges(int $countryId): array { return $this->db?->select('SELECT DISTINCT c.stock_exchange FROM companies c JOIN investment_offerings o ON o.company_id=c.id WHERE o.country_id=? AND c.status="ACTIVE" AND o.is_active=1 ORDER BY c.stock_exchange',[$countryId])??[]; }
    public function adminList(array $filters,Pagination $pagination): array {
        if(!$this->db)return ['items'=>[],'total'=>0];$where=['1=1'];$p=[];
        if(($filters['q']??'')!==''){$where[]='(c.display_name LIKE ? OR c.legal_name LIKE ? OR c.ticker LIKE ?)';$like='%'.$filters['q'].'%';array_push($p,$like,$like,$like);}
        if(($filters['status']??'')!==''){$where[]='c.status=?';$p[]=$filters['status'];}
        if(($filters['country']??'')!==''){$where[]='o.country_id=?';$p[]=(int)$filters['country'];}
        $base=' FROM companies c LEFT JOIN investment_offerings o ON o.company_id=c.id LEFT JOIN countries cp ON cp.id=o.country_id WHERE '.implode(' AND ',$where);
        $total=(int)$this->db->scalar('SELECT COUNT(DISTINCT c.id)'.$base,$p);
        $items=$this->db->select('SELECT c.*,o.id offering_id,o.country_id,o.currency_code,o.share_price_minor,o.profit_type,o.projected_profit_value,o.duration_value,o.duration_unit,o.is_active offering_active,cp.name country_name'.$base.' ORDER BY c.updated_at DESC,c.id DESC LIMIT '.(int)$pagination->offset().','.(int)$pagination->perPage,$p);
        return ['items'=>$items,'total'=>$total];
    }
    public function byId(int $id): ?array { return $this->db?->one('SELECT c.*,o.id offering_id,o.country_id,o.currency_code,o.share_price_minor,o.minimum_purchase_quantity,o.maximum_purchase_quantity,o.maximum_user_holding,o.total_available_quantity,o.fractional_shares_allowed,o.quantity_decimal_places,o.profit_type,o.projected_profit_value,o.fixed_profit_basis,o.duration_value,o.duration_unit,o.selling_enabled,o.is_active offering_active,o.is_featured offering_featured FROM companies c LEFT JOIN investment_offerings o ON o.company_id=c.id WHERE c.id=? ORDER BY o.id LIMIT 1',[$id]); }
    public function images(int $companyId): array { return $this->db?->select('SELECT * FROM company_images WHERE company_id=? ORDER BY image_type,sort_order,id',[$companyId])??[]; }
    public function sources(int $companyId): array { return $this->db?->select('SELECT * FROM company_sources WHERE company_id=? ORDER BY verified_at DESC,id DESC',[$companyId])??[]; }
    public function archive(int $id,bool $archive): void { $this->db?->execute('UPDATE companies SET status=?,updated_at=NOW() WHERE id=?',[$archive?'ARCHIVED':'ACTIVE',$id]); if($archive)$this->db?->execute('UPDATE investment_offerings SET is_active=0,updated_at=NOW() WHERE company_id=?',[$id]); }
    public function save(array $company,array $offering,?int $id=null): int {
        if(!$this->db)throw new \RuntimeException('Database unavailable.');
        return $this->db->transaction(function(Database $db)use($company,$offering,$id){
            if($id===null){
                $db->execute('INSERT INTO companies(public_id,legal_name,display_name,slug,ticker,stock_exchange,exchange_mic,isin,listing_country_code,headquarters_country_code,headquarters_city,sector,industry,year_founded,website_url,investor_relations_url,short_description,long_profile,status,is_featured,sort_order,last_verified_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())',[$this->uuid(),$company['legal_name'],$company['display_name'],$company['slug'],$company['ticker'],$company['stock_exchange'],$company['exchange_mic']?:null,$company['isin']?:null,$company['listing_country_code']?:null,$company['headquarters_country_code']?:null,$company['headquarters_city']?:null,$company['sector']?:null,$company['industry']?:null,$company['year_founded']!==''?(int)$company['year_founded']:null,$company['website_url']?:null,$company['investor_relations_url']?:null,$company['short_description']?:null,$company['long_profile']?:null,$company['status'],$company['is_featured'],$company['sort_order'],$company['last_verified_at']?:null]);
                $id=(int)$db->pdo()->lastInsertId();
            } else {
                $db->execute('UPDATE companies SET legal_name=?,display_name=?,slug=?,ticker=?,stock_exchange=?,exchange_mic=?,isin=?,listing_country_code=?,headquarters_country_code=?,headquarters_city=?,sector=?,industry=?,year_founded=?,website_url=?,investor_relations_url=?,short_description=?,long_profile=?,status=?,is_featured=?,sort_order=?,last_verified_at=?,updated_at=NOW() WHERE id=?',[$company['legal_name'],$company['display_name'],$company['slug'],$company['ticker'],$company['stock_exchange'],$company['exchange_mic']?:null,$company['isin']?:null,$company['listing_country_code']?:null,$company['headquarters_country_code']?:null,$company['headquarters_city']?:null,$company['sector']?:null,$company['industry']?:null,$company['year_founded']!==''?(int)$company['year_founded']:null,$company['website_url']?:null,$company['investor_relations_url']?:null,$company['short_description']?:null,$company['long_profile']?:null,$company['status'],$company['is_featured'],$company['sort_order'],$company['last_verified_at']?:null,$id]);
            }
            $existing=$db->one('SELECT * FROM investment_offerings WHERE company_id=? AND country_id=? LIMIT 1',[$id,$offering['country_id']]);
            if($existing){
                $actor=!empty($_SESSION['user_id'])?(int)$_SESSION['user_id']:null;
                if((int)$existing['share_price_minor']!==(int)$offering['share_price_minor'])$db->execute('INSERT INTO investment_price_history(offering_id,old_price_minor,new_price_minor,reason,changed_by_user_id) VALUES (?,?,?,?,?)',[$existing['id'],$existing['share_price_minor'],$offering['share_price_minor'],$offering['change_reason']?:null,$actor]);
                $changed=[];foreach(['minimum_purchase_quantity','maximum_purchase_quantity','maximum_user_holding','total_available_quantity','fractional_shares_allowed','quantity_decimal_places','profit_type','projected_profit_value','fixed_profit_basis','duration_value','duration_unit','selling_enabled','is_active','is_featured'] as $f)if((string)($existing[$f]??'')!==(string)($offering[$f]??''))$changed[$f]=['old'=>$existing[$f]??null,'new'=>$offering[$f]??null];
                if($changed)$db->execute('INSERT INTO investment_offering_history(offering_id,changed_by_user_id,changed_fields,reason) VALUES (?,?,?,?)',[$existing['id'],$actor,json_encode($changed,JSON_THROW_ON_ERROR),$offering['change_reason']?:null]);
                $db->execute('UPDATE investment_offerings SET currency_code=?,share_price_minor=?,minimum_purchase_quantity=?,maximum_purchase_quantity=?,maximum_user_holding=?,total_available_quantity=?,fractional_shares_allowed=?,quantity_decimal_places=?,profit_type=?,projected_profit_value=?,fixed_profit_basis=?,duration_value=?,duration_unit=?,selling_enabled=?,is_active=?,is_featured=?,available_from=?,available_until=?,updated_at=NOW() WHERE id=?',[$offering['currency_code'],$offering['share_price_minor'],$offering['minimum_purchase_quantity'],$offering['maximum_purchase_quantity'],$offering['maximum_user_holding'],$offering['total_available_quantity'],$offering['fractional_shares_allowed'],$offering['quantity_decimal_places'],$offering['profit_type'],$offering['projected_profit_value'],$offering['fixed_profit_basis'],$offering['duration_value'],$offering['duration_unit'],$offering['selling_enabled'],$offering['is_active'],$offering['is_featured'],$offering['available_from'],$offering['available_until'],$existing['id']]);
            } else {
                $db->execute('INSERT INTO investment_offerings(public_id,company_id,country_id,currency_code,share_price_minor,minimum_purchase_quantity,maximum_purchase_quantity,maximum_user_holding,total_available_quantity,fractional_shares_allowed,quantity_decimal_places,profit_type,projected_profit_value,fixed_profit_basis,duration_value,duration_unit,selling_enabled,is_active,is_featured,available_from,available_until,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())',[$this->uuid(),$id,$offering['country_id'],$offering['currency_code'],$offering['share_price_minor'],$offering['minimum_purchase_quantity'],$offering['maximum_purchase_quantity'],$offering['maximum_user_holding'],$offering['total_available_quantity'],$offering['fractional_shares_allowed'],$offering['quantity_decimal_places'],$offering['profit_type'],$offering['projected_profit_value'],$offering['fixed_profit_basis'],$offering['duration_value'],$offering['duration_unit'],$offering['selling_enabled'],$offering['is_active'],$offering['is_featured'],$offering['available_from'],$offering['available_until']]);
            }
            $db->execute('INSERT IGNORE INTO company_country_assignments(company_id,country_id) VALUES (?,?)',[$id,$offering['country_id']]);
            return $id;
        });
    }
    private function publicWhere(int $countryId,array $filters): array {
        $where=['o.country_id=?','c.status="ACTIVE"','o.is_active=1','(o.available_from IS NULL OR o.available_from<=NOW())','(o.available_until IS NULL OR o.available_until>=NOW())'];$p=[$countryId];
        if(($filters['q']??'')!==''){$where[]='(c.display_name LIKE ? OR c.ticker LIKE ? OR c.industry LIKE ?)';$like='%'.$filters['q'].'%';array_push($p,$like,$like,$like);}
        if(($filters['industry']??'')!==''){$where[]='c.industry=?';$p[]=$filters['industry'];}
        if(($filters['exchange']??'')!==''){$where[]='c.stock_exchange=?';$p[]=$filters['exchange'];}
        if(($filters['featured']??'')==='1')$where[]='(o.is_featured=1 OR c.is_featured=1)';
        return [$where,$p];
    }
    private function uuid(): string { $h=bin2hex(random_bytes(16)); return sprintf('%s-%s-%s-%s-%s',substr($h,0,8),substr($h,8,4),substr($h,12,4),substr($h,16,4),substr($h,20,12)); }
}
