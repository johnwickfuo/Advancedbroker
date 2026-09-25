<?php
declare(strict_types=1);

namespace Database\Seeds;

use App\Security\Crypto;
use App\Support\Database;

final class AiTradingSeeder
{
    public function run(Database $db): void
    {
        if (!(bool)$db->scalar("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ai_trading_categories'")) {
            return;
        }

        $key=(string)env('APP_KEY','');
        if($key==='') throw new \RuntimeException('APP_KEY is required to seed AI trading codes.');
        $crypto=new Crypto($key);

        foreach($this->categories() as $category){
            $existing=$db->one('SELECT * FROM ai_trading_categories WHERE slug=? LIMIT 1',[$category['slug']]);
            if($existing) continue;

            $db->transaction(function(Database $db)use($category,$crypto):void{
                $db->execute(
                    'INSERT INTO ai_trading_categories(public_id,name,slug,description,price_usd_minor,profit_percent,duration_value,duration_unit,code_batch_size,is_active,created_by_user_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,50,1,NULL,NOW(),NOW())',
                    [
                        $this->uuid(),$category['name'],$category['slug'],$category['description'],
                        $category['price_usd_minor'],$category['profit_percent'],
                        $category['duration_value'],$category['duration_unit']
                    ]
                );
                $categoryId=(int)$db->pdo()->lastInsertId();
                $this->generateCodes($db,$crypto,$categoryId,1,50);
            });
        }
    }

    private function categories(): array
    {
        return [
            [
                'name'=>'Beginner',
                'slug'=>'beginner',
                'description'=>'An entry-level AI trading category with a lower starting amount and a 30-day activation cycle.',
                'price_usd_minor'=>10000,
                'profit_percent'=>'50.0000',
                'duration_value'=>30,
                'duration_unit'=>'DAYS',
            ],
            [
                'name'=>'Growth',
                'slug'=>'growth',
                'description'=>'A mid-range AI trading category designed for users who want a larger fixed-profit cycle.',
                'price_usd_minor'=>25000,
                'profit_percent'=>'55.0000',
                'duration_value'=>45,
                'duration_unit'=>'DAYS',
            ],
            [
                'name'=>'Advanced',
                'slug'=>'advanced',
                'description'=>'A higher-value AI trading category with an extended trading duration and fixed-profit terms.',
                'price_usd_minor'=>50000,
                'profit_percent'=>'65.0000',
                'duration_value'=>60,
                'duration_unit'=>'DAYS',
            ],
            [
                'name'=>'Professional',
                'slug'=>'professional',
                'description'=>'A professional-tier AI trading category with a larger allocation and longer activation cycle.',
                'price_usd_minor'=>100000,
                'profit_percent'=>'80.0000',
                'duration_value'=>90,
                'duration_unit'=>'DAYS',
            ],
            [
                'name'=>'Elite',
                'slug'=>'elite',
                'description'=>'A premium AI trading category for larger allocations over an extended trading cycle.',
                'price_usd_minor'=>250000,
                'profit_percent'=>'100.0000',
                'duration_value'=>120,
                'duration_unit'=>'DAYS',
            ],
            [
                'name'=>'Premier',
                'slug'=>'premier',
                'description'=>'A premium AI trading category with a larger allocation and longer activation cycle.',
                'price_usd_minor'=>500000,
                'profit_percent'=>'115.0000',
                'duration_value'=>150,
                'duration_unit'=>'DAYS',
            ],
            [
                'name'=>'Executive',
                'slug'=>'executive',
                'description'=>'An upper-tier AI trading category designed for substantial allocations and extended cycles.',
                'price_usd_minor'=>1000000,
                'profit_percent'=>'130.0000',
                'duration_value'=>180,
                'duration_unit'=>'DAYS',
            ],
            [
                'name'=>'Institutional',
                'slug'=>'institutional',
                'description'=>'A high-capacity AI trading category with long-duration fixed-profit terms.',
                'price_usd_minor'=>2500000,
                'profit_percent'=>'150.0000',
                'duration_value'=>210,
                'duration_unit'=>'DAYS',
            ],
            [
                'name'=>'Apex',
                'slug'=>'apex',
                'description'=>'A top-tier ApexTrades AI trading category for large allocations and extended cycles.',
                'price_usd_minor'=>5000000,
                'profit_percent'=>'175.0000',
                'duration_value'=>270,
                'duration_unit'=>'DAYS',
            ],
            [
                'name'=>'Quantum',
                'slug'=>'quantum',
                'description'=>'The highest seeded AI trading category with the largest allocation and longest cycle.',
                'price_usd_minor'=>10000000,
                'profit_percent'=>'200.0000',
                'duration_value'=>365,
                'duration_unit'=>'DAYS',
            ],
        ];
    }

    private function generateCodes(Database $db,Crypto $crypto,int $categoryId,int $batch,int $count): void
    {
        for($i=0;$i<$count;$i++){
            do{
                $plain=$this->newCode();
                $normalized=strtoupper(preg_replace('/[^A-Z0-9]/i','',$plain)??'');
                $hash=hash('sha256',$normalized);
                $exists=(bool)$db->scalar('SELECT COUNT(*) FROM ai_trading_codes WHERE code_hash=?',[$hash]);
            }while($exists);

            $db->execute(
                'INSERT INTO ai_trading_codes(public_id,category_id,batch_number,code_hash,code_encrypted,status,generated_at) VALUES (?,?,?,?,?,"AVAILABLE",NOW())',
                [$this->uuid(),$categoryId,$batch,$hash,$crypto->encrypt($plain)]
            );
        }
    }

    private function newCode(): string
    {
        $hex=strtoupper(bin2hex(random_bytes(6)));
        return 'AI-'.substr($hex,0,4).'-'.substr($hex,4,4).'-'.substr($hex,8,4);
    }

    private function uuid(): string
    {
        $b=random_bytes(16);
        $b[6]=chr((ord($b[6])&0x0f)|0x40);
        $b[8]=chr((ord($b[8])&0x3f)|0x80);
        $h=bin2hex($b);
        return substr($h,0,8).'-'.substr($h,8,4).'-'.substr($h,12,4).'-'.substr($h,16,4).'-'.substr($h,20,12);
    }
}
