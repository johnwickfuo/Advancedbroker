<?php
declare(strict_types=1);

namespace App\Services;

use App\Security\Crypto;
use App\Support\Database;

final class AiTradingService
{
    public function __construct(
        private ?Database $db,
        private WalletService $wallets,
        private NotificationService $notifications,
        private Crypto $crypto,
        private array $config
    ) {}

    public function publicCategories(string $currencyCode): array
    {
        if(!$this->db) return [];
        $rows=$this->db->select('SELECT * FROM ai_trading_categories WHERE is_active=1 ORDER BY price_usd_minor ASC,id ASC LIMIT 10');
        foreach($rows as &$row){
            $row['quote']=$this->quote($row,$currencyCode);
            $row['available_codes']=(int)$this->db->scalar('SELECT COUNT(*) FROM ai_trading_codes WHERE category_id=? AND status="AVAILABLE"',[$row['id']]);
        }
        unset($row);
        return $rows;
    }

    public function adminCategories(): array
    {
        if(!$this->db) return [];
        return $this->db->select('SELECT c.*,
            SUM(CASE WHEN k.status="AVAILABLE" THEN 1 ELSE 0 END) available_codes,
            SUM(CASE WHEN k.status="SOLD" THEN 1 ELSE 0 END) sold_codes,
            SUM(CASE WHEN k.status="ACTIVATED" THEN 1 ELSE 0 END) active_codes,
            SUM(CASE WHEN k.status="COMPLETED" THEN 1 ELSE 0 END) completed_codes
            FROM ai_trading_categories c
            LEFT JOIN ai_trading_codes k ON k.category_id=c.id
            GROUP BY c.id ORDER BY c.created_at DESC');
    }

    public function adminPurchases(int $limit=100): array
    {
        return $this->db?->select('SELECT p.*,u.email,c.name category_name FROM ai_trading_purchases p JOIN users u ON u.id=p.user_id JOIN ai_trading_categories c ON c.id=p.category_id ORDER BY p.purchased_at DESC LIMIT '.max(1,min($limit,500)))??[];
    }

    public function findCategory(string $publicId): ?array
    {
        return $this->db?->one('SELECT * FROM ai_trading_categories WHERE public_id=?',[$publicId]);
    }

    public function createCategory(array $data,int $adminId): array
    {
        if(!$this->db) throw new \RuntimeException('Database unavailable.');
        $name=trim((string)($data['name']??''));
        $description=trim((string)($data['description']??''));
        $price=$this->usdMinor((string)($data['price_usd']??''));
        $profit=$this->profitPercent((string)($data['profit_percent']??''));
        $duration=(int)($data['duration_value']??0);
        $unit=strtoupper(trim((string)($data['duration_unit']??'DAYS')));
        if($name==='') throw new \InvalidArgumentException('Category name is required.');
        if($duration<1) throw new \InvalidArgumentException('Duration must be at least 1.');
        if(!in_array($unit,['HOURS','DAYS','WEEKS','MONTHS'],true)) throw new \InvalidArgumentException('Invalid duration unit.');
        $slug=$this->uniqueSlug($name);
        $public=$this->uuid();
        $batch=max(1,(int)($this->config['default_batch_size']??50));

        return $this->db->transaction(function(Database $db)use($name,$description,$price,$profit,$duration,$unit,$slug,$public,$batch,$adminId){
            $db->execute('INSERT INTO ai_trading_categories(public_id,name,slug,description,price_usd_minor,profit_percent,duration_value,duration_unit,code_batch_size,is_active,created_by_user_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,1,?,NOW(),NOW())',[$public,$name,$slug,$description?:null,$price,$profit,$duration,$unit,$batch,$adminId]);
            $id=(int)$db->pdo()->lastInsertId();
            $this->generateBatchLocked($id,$batch);
            return $db->one('SELECT * FROM ai_trading_categories WHERE id=?',[$id]);
        });
    }

    public function updateCategory(string $publicId,array $data): array
    {
        if(!$this->db) throw new \RuntimeException('Database unavailable.');
        $category=$this->db->one('SELECT * FROM ai_trading_categories WHERE public_id=?',[$publicId]);
        if(!$category) throw new \RuntimeException('AI trading category not found.');
        $name=trim((string)($data['name']??$category['name']));
        $description=trim((string)($data['description']??$category['description']));
        $price=$this->usdMinor((string)($data['price_usd']??((int)$category['price_usd_minor']/100)));
        $profit=$this->profitPercent((string)($data['profit_percent']??$category['profit_percent']));
        $duration=max(1,(int)($data['duration_value']??$category['duration_value']));
        $unit=strtoupper(trim((string)($data['duration_unit']??$category['duration_unit'])));
        if(!in_array($unit,['HOURS','DAYS','WEEKS','MONTHS'],true)) throw new \InvalidArgumentException('Invalid duration unit.');
        $active=!empty($data['is_active'])?1:0;
        $this->db->execute('UPDATE ai_trading_categories SET name=?,description=?,price_usd_minor=?,profit_percent=?,duration_value=?,duration_unit=?,is_active=?,updated_at=NOW() WHERE id=?',[$name,$description?:null,$price,$profit,$duration,$unit,$active,$category['id']]);
        return $this->db->one('SELECT * FROM ai_trading_categories WHERE id=?',[$category['id']]);
    }

    public function buy(array $user,array $country,string $categoryPublicId): array
    {
        if(!$this->db) throw new \RuntimeException('Database unavailable.');
        if(!app('account_access')->mayPerformFinancialAction($user)) throw new \RuntimeException('Your account is not permitted to make this purchase.');

        $result=$this->db->transaction(function(Database $db)use($user,$country,$categoryPublicId){
            $category=$db->lockForUpdate('SELECT * FROM ai_trading_categories WHERE public_id=?',[$categoryPublicId]);
            if(!$category||!$category['is_active']) throw new \RuntimeException('This AI trading category is unavailable.');

            $code=$db->lockForUpdate('SELECT * FROM ai_trading_codes WHERE category_id=? AND status="AVAILABLE" ORDER BY id LIMIT 1',[$category['id']]);
            if(!$code){
                $this->generateBatchLocked((int)$category['id'],(int)$category['code_batch_size']);
                $code=$db->lockForUpdate('SELECT * FROM ai_trading_codes WHERE category_id=? AND status="AVAILABLE" ORDER BY id LIMIT 1',[$category['id']]);
            }
            if(!$code) throw new \RuntimeException('No AI trading code is currently available.');

            $wallet=$this->wallets->walletFor($user,$country);
            if(!$wallet||$wallet['status']!=='ACTIVE') throw new \RuntimeException('Your wallet is unavailable.');
            $quote=$this->quote($category,(string)$wallet['currency_code']);
            $public='AIT-'.strtoupper(bin2hex(random_bytes(8)));
            $reference='AIC-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(5)));

            $ledger=$this->wallets->move((int)$wallet['id'],'AI_CODE_PURCHASE','DEBIT',$quote['purchase_amount_minor'],'AI trading code purchase','ai-trading-purchase',$public,null);

            $db->execute('INSERT INTO ai_trading_purchases(public_id,reference,user_id,wallet_id,category_id,code_id,category_name_snapshot,price_usd_minor_snapshot,profit_percent_snapshot,duration_value_snapshot,duration_unit_snapshot,fx_rate_snapshot,fx_snapshot_date,currency_code,purchase_amount_minor,profit_amount_minor,maturity_payout_minor,status,purchase_ledger_transaction_id,purchased_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,"PURCHASED",?,NOW(),NOW(),NOW())',[
                $public,$reference,$user['id'],$wallet['id'],$category['id'],$code['id'],$category['name'],$category['price_usd_minor'],$category['profit_percent'],$category['duration_value'],$category['duration_unit'],$quote['fx_rate'],$this->config['fx_snapshot_date'],$wallet['currency_code'],$quote['purchase_amount_minor'],$quote['profit_amount_minor'],$quote['maturity_payout_minor'],$ledger['id']
            ]);
            $purchaseId=(int)$db->pdo()->lastInsertId();
            $db->execute('UPDATE ai_trading_codes SET purchase_id=?,status="SOLD",sold_at=NOW() WHERE id=? AND status="AVAILABLE"',[$purchaseId,$code['id']]);

            $remaining=(int)$db->scalar('SELECT COUNT(*) FROM ai_trading_codes WHERE category_id=? AND status="AVAILABLE"',[$category['id']]);
            if($remaining===0) $this->generateBatchLocked((int)$category['id'],(int)$category['code_batch_size']);

            $purchase=$db->one('SELECT * FROM ai_trading_purchases WHERE id=?',[$purchaseId]);
            if(!$purchase) throw new \RuntimeException('AI trading purchase could not be created.');
            $purchase['activation_code']=$this->crypto->decrypt((string)$code['code_encrypted']);
            return $purchase;
        });

        $this->notifications->transactional((int)$user['id'],'ai_code_purchased','AI trading code purchased','Your unique code is ready to activate.',['url'=>'/dashboard/ai-trading/'.$result['public_id'].'/activate']);
        return $result;
    }

    public function activate(int $userId,string $purchasePublicId,string $submittedCode): array
    {
        if(!$this->db) throw new \RuntimeException('Database unavailable.');
        $normalized=$this->normalizeCode($submittedCode);
        if($normalized==='') throw new \InvalidArgumentException('Enter your AI trading code.');

        $result=$this->db->transaction(function(Database $db)use($userId,$purchasePublicId,$normalized){
            $purchase=$db->lockForUpdate('SELECT * FROM ai_trading_purchases WHERE public_id=? AND user_id=?',[$purchasePublicId,$userId]);
            if(!$purchase) throw new \RuntimeException('AI trading purchase not found.');
            if($purchase['status']==='ACTIVE'||$purchase['status']==='COMPLETED') return $purchase;
            if($purchase['status']!=='PURCHASED') throw new \RuntimeException('This AI trading code cannot be activated.');

            $code=$db->lockForUpdate('SELECT * FROM ai_trading_codes WHERE id=? AND purchase_id=?',[$purchase['code_id'],$purchase['id']]);
            if(!$code||$code['status']!=='SOLD') throw new \RuntimeException('This AI trading code is unavailable.');
            if(!hash_equals((string)$code['code_hash'],hash('sha256',$normalized))) throw new \RuntimeException('The AI trading code is invalid.');

            $activated=new \DateTimeImmutable('now');
            $matures=$this->maturityDate($activated,(int)$purchase['duration_value_snapshot'],(string)$purchase['duration_unit_snapshot']);
            $db->execute('UPDATE ai_trading_purchases SET status="ACTIVE",activated_at=?,matures_at=?,updated_at=NOW() WHERE id=?',[$activated->format('Y-m-d H:i:s'),$matures->format('Y-m-d H:i:s'),$purchase['id']]);
            $db->execute('UPDATE ai_trading_codes SET status="ACTIVATED",activated_at=? WHERE id=?',[$activated->format('Y-m-d H:i:s'),$code['id']]);
            return $db->one('SELECT * FROM ai_trading_purchases WHERE id=?',[$purchase['id']]);
        });

        $this->notifications->transactional($userId,'ai_trade_activated','AI trading activated','Your AI trading cycle is now active.',['url'=>'/dashboard/ai-trading']);
        return $this->withProgress($result);
    }

    public function forUser(int $userId): array
    {
        if(!$this->db) return [];
        $rows=$this->db->select('SELECT p.*,c.name category_name,k.code_encrypted FROM ai_trading_purchases p JOIN ai_trading_categories c ON c.id=p.category_id JOIN ai_trading_codes k ON k.id=p.code_id WHERE p.user_id=? ORDER BY p.purchased_at DESC',[$userId]);
        foreach($rows as &$row){
            $row['activation_code']=$this->crypto->decrypt((string)$row['code_encrypted']);
            $row=$this->withProgress($row);
        }
        unset($row);
        return $rows;
    }

    public function findForUser(int $userId,string $publicId): ?array
    {
        if(!$this->db) return null;
        $row=$this->db->one('SELECT p.*,c.name category_name,k.code_encrypted FROM ai_trading_purchases p JOIN ai_trading_categories c ON c.id=p.category_id JOIN ai_trading_codes k ON k.id=p.code_id WHERE p.user_id=? AND p.public_id=?',[$userId,$publicId]);
        if(!$row) return null;
        $row['activation_code']=$this->crypto->decrypt((string)$row['code_encrypted']);
        return $this->withProgress($row);
    }

    public function activeForUser(int $userId,int $limit=4): array
    {
        return array_slice(array_values(array_filter($this->forUser($userId),static fn(array $r):bool=>in_array($r['status'],['PURCHASED','ACTIVE'],true))),0,$limit);
    }

    public function matureDue(bool $dry=false,int $limit=100): array
    {
        if(!$this->db) return [];
        $due=$this->db->select('SELECT id FROM ai_trading_purchases WHERE status="ACTIVE" AND matures_at IS NOT NULL AND matures_at<=NOW() ORDER BY matures_at LIMIT '.max(1,min($limit,500)));
        $done=[];
        foreach($due as $row){
            if($dry){$done[]=$row['id'];continue;}
            $purchase=$this->db->transaction(function(Database $db)use($row){
                $p=$db->lockForUpdate('SELECT * FROM ai_trading_purchases WHERE id=?',[$row['id']]);
                if(!$p||$p['status']!=='ACTIVE'||!$p['matures_at']||strtotime((string)$p['matures_at'])>time()) return null;
                $principal=$this->wallets->move((int)$p['wallet_id'],'AI_PRINCIPAL_RETURN','CREDIT',(int)$p['purchase_amount_minor'],'AI trading purchase amount returned','ai-trading-principal',(string)$p['public_id']);
                $profit=$this->wallets->move((int)$p['wallet_id'],'AI_PROFIT','CREDIT',(int)$p['profit_amount_minor'],'AI trading fixed profit credited','ai-trading-profit',(string)$p['public_id']);
                $db->execute('UPDATE ai_trading_purchases SET status="COMPLETED",completed_at=NOW(),principal_return_ledger_transaction_id=?,profit_ledger_transaction_id=?,updated_at=NOW() WHERE id=?',[$principal['id'],$profit['id'],$p['id']]);
                $db->execute('UPDATE ai_trading_codes SET status="COMPLETED",completed_at=NOW() WHERE id=?',[$p['code_id']]);
                return $p;
            });
            if($purchase){
                $done[]=$purchase['id'];
                $this->notifications->transactional((int)$purchase['user_id'],'ai_trade_completed','AI trading completed','Your purchase amount and fixed profit have been credited to your wallet.',['url'=>'/dashboard/ai-trading']);
            }
        }
        return $done;
    }

    public function quote(array $category,string $currencyCode): array
    {
        $currencyCode=strtoupper($currencyCode);
        $rate=$this->config['rates'][$currencyCode]??null;
        if(!is_numeric($rate)||$rate<=0) throw new \RuntimeException('A locked AI trading exchange rate is not available for '.$currencyCode.'.');
        $scale=(int)config('localization.currency_scales.'.$currencyCode,2);
        $factor=10 ** $scale;
        $usd=((int)$category['price_usd_minor'])/100;
        $purchase=(int)round($usd*(float)$rate*$factor,0,PHP_ROUND_HALF_UP);
        $profit=(int)round($purchase*((float)$category['profit_percent']/100),0,PHP_ROUND_HALF_UP);
        return [
            'currency_code'=>$currencyCode,
            'fx_rate'=>(float)$rate,
            'fx_snapshot_date'=>(string)$this->config['fx_snapshot_date'],
            'purchase_amount_minor'=>$purchase,
            'profit_amount_minor'=>$profit,
            'maturity_payout_minor'=>$purchase+$profit,
        ];
    }

    private function generateBatchLocked(int $categoryId,int $size): void
    {
        if(!$this->db) return;
        $size=max(1,min($size,500));
        $batch=(int)($this->db->scalar('SELECT COALESCE(MAX(batch_number),0)+1 FROM ai_trading_codes WHERE category_id=?',[$categoryId])??1);
        for($i=0;$i<$size;$i++){
            do{
                $plain=$this->newCode();
                $hash=hash('sha256',$this->normalizeCode($plain));
                $exists=(bool)$this->db->scalar('SELECT COUNT(*) FROM ai_trading_codes WHERE code_hash=?',[$hash]);
            }while($exists);
            $this->db->execute('INSERT INTO ai_trading_codes(public_id,category_id,batch_number,code_hash,code_encrypted,status,generated_at) VALUES (?,?,?,?,?,"AVAILABLE",NOW())',[$this->uuid(),$categoryId,$batch,$hash,$this->crypto->encrypt($plain)]);
        }
    }

    private function withProgress(array $row): array
    {
        if($row['status']==='COMPLETED'){
            $row['progress_percent']=100.0;
            return $row;
        }
        if($row['status']!=='ACTIVE'||empty($row['activated_at'])||empty($row['matures_at'])){
            $row['progress_percent']=0.0;
            return $row;
        }
        $start=strtotime((string)$row['activated_at']);
        $end=strtotime((string)$row['matures_at']);
        $span=max(1,$end-$start);
        $elapsed=max(0,min($span,time()-$start));
        $row['progress_percent']=round(($elapsed/$span)*100,1);
        return $row;
    }

    private function maturityDate(\DateTimeImmutable $start,int $value,string $unit): \DateTimeImmutable
    {
        return match($unit){
            'HOURS'=>$start->modify('+'.$value.' hours'),
            'DAYS'=>$start->modify('+'.$value.' days'),
            'WEEKS'=>$start->modify('+'.$value.' weeks'),
            'MONTHS'=>$start->modify('+'.$value.' months'),
            default=>throw new \InvalidArgumentException('Invalid AI trading duration unit.'),
        };
    }

    private function usdMinor(string $amount): int
    {
        $amount=trim($amount);
        if(!preg_match('/^\d+(?:\.\d{1,2})?$/',$amount)) throw new \InvalidArgumentException('Enter a valid USD price.');
        [$whole,$fraction]=array_pad(explode('.',$amount,2),2,'');
        $minor=((int)$whole*100)+(int)str_pad($fraction,2,'0');
        if($minor<1) throw new \InvalidArgumentException('Price must be greater than zero.');
        return $minor;
    }

    private function profitPercent(string $value): string
    {
        $value=trim($value);
        if(!preg_match('/^\d+(?:\.\d{1,4})?$/',$value)||(float)$value<=0||(float)$value>1000) throw new \InvalidArgumentException('Enter a valid profit percentage.');
        return number_format((float)$value,4,'.','');
    }

    private function uniqueSlug(string $name): string
    {
        $base=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$name)??'','-'))?:'ai-category';
        $slug=$base;$n=2;
        while((bool)$this->db?->scalar('SELECT COUNT(*) FROM ai_trading_categories WHERE slug=?',[$slug])){$slug=$base.'-'.$n++;}
        return $slug;
    }

    private function newCode(): string
    {
        $hex=strtoupper(bin2hex(random_bytes(6)));
        return 'AI-'.substr($hex,0,4).'-'.substr($hex,4,4).'-'.substr($hex,8,4);
    }

    private function normalizeCode(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/i','',$code)??'');
    }

    private function uuid(): string
    {
        $b=random_bytes(16);$b[6]=chr((ord($b[6])&0x0f)|0x40);$b[8]=chr((ord($b[8])&0x3f)|0x80);$h=bin2hex($b);
        return substr($h,0,8).'-'.substr($h,8,4).'-'.substr($h,12,4).'-'.substr($h,16,4).'-'.substr($h,20,12);
    }
}
