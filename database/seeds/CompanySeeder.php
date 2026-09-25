<?php
declare(strict_types=1);

namespace Database\Seeds;

use App\Support\Database;

/**
 * Seeds a reusable factual company pool, then creates country-scoped platform
 * offerings from that pool. Company facts and platform offering terms remain
 * separate. Existing administrator-edited offerings are never overwritten.
 *
 * Targets on a fresh/partially seeded database:
 * - each enabled national market: at least 10 active offerings, 5 featured
 * - Global: at least 20 active offerings, 5 featured
 */
final class CompanySeeder
{
    public function run(Database $db): void
    {
        $masterIds = $this->ensureMasterCompanies($db);
        $countries = $db->select(
            'SELECT id,code,slug,name,currency_code,is_global FROM countries WHERE is_global=1 OR (is_active=1 AND is_enabled=1) ORDER BY is_global ASC,sort_order ASC,id ASC'
        );

        foreach ($countries as $country) {
            $this->fillCountry($db, $country, $masterIds);
        }
    }

    private function ensureMasterCompanies(Database $db): array
    {
        $ids = [];
        foreach ($this->companies() as $sort => $company) {
            $existing = $db->one('SELECT id FROM companies WHERE seed_key=? LIMIT 1', [$company['key']]);

            if (!$existing) {
                $db->execute(
                    'INSERT INTO companies(public_id,seed_key,legal_name,display_name,slug,ticker,stock_exchange,exchange_mic,listing_country_code,headquarters_country_code,headquarters_city,sector,industry,website_url,investor_relations_url,short_description,status,is_featured,sort_order,last_verified_at,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,"ACTIVE",0,?,CURDATE(),NOW(),NOW())',
                    [
                        $this->uuid(),$company['key'],$company['legal'],$company['name'],$company['slug'],
                        $company['ticker'],$company['exchange'],$company['mic'],$company['listing_country'],
                        $company['hq_country'],$company['city'],$company['sector'],$company['industry'],
                        $company['website'],$company['ir'],$company['description'],$sort
                    ]
                );
                $id = (int)$db->pdo()->lastInsertId();
            } else {
                $id = (int)$existing['id'];
                $db->execute(
                    'UPDATE companies SET legal_name=?,display_name=?,slug=?,ticker=?,stock_exchange=?,exchange_mic=?,listing_country_code=?,headquarters_country_code=?,headquarters_city=?,sector=?,industry=?,website_url=?,investor_relations_url=?,short_description=?,status="ACTIVE",last_verified_at=CURDATE(),updated_at=NOW() WHERE id=?',
                    [
                        $company['legal'],$company['name'],$company['slug'],$company['ticker'],$company['exchange'],
                        $company['mic'],$company['listing_country'],$company['hq_country'],$company['city'],$company['sector'],
                        $company['industry'],$company['website'],$company['ir'],$company['description'],$id
                    ]
                );
            }

            $ids[] = $id;
            $hasSource = (int)$db->scalar(
                'SELECT COUNT(*) FROM company_sources WHERE company_id=? AND source_url=?',
                [$id,$company['ir']]
            );
            if ($hasSource === 0) {
                $db->execute(
                    'INSERT INTO company_sources(company_id,source_type,title,source_url,publisher,verified_at) VALUES (?,"OFFICIAL_COMPANY",?,?,?,CURDATE())',
                    [$id,$company['name'].' investor information',$company['ir'],$company['name']]
                );
            }
        }
        return $ids;
    }

    private function fillCountry(Database $db, array $country, array $masterIds): void
    {
        $countryId = (int)$country['id'];
        $target = !empty($country['is_global']) ? 20 : 10;

        $active = $db->select(
            'SELECT o.company_id,o.is_featured FROM investment_offerings o JOIN companies c ON c.id=o.company_id WHERE o.country_id=? AND o.is_active=1 AND c.status="ACTIVE"',
            [$countryId]
        );
        $activeCount = count($active);
        $featuredCount = count(array_filter($active, static fn(array $row): bool => (bool)$row['is_featured']));

        if ($activeCount >= $target) {
            return;
        }

        $allExisting = $db->select('SELECT company_id FROM investment_offerings WHERE country_id=?', [$countryId]);
        $used = [];
        foreach ($allExisting as $row) $used[(int)$row['company_id']] = true;

        $poolCount = count($masterIds);
        if ($poolCount === 0) return;

        $offset = abs((int)crc32((string)$country['slug'])) % $poolCount;
        $scan = 0;
        $slot = $activeCount;

        while ($activeCount < $target && $scan < ($poolCount * 2)) {
            $companyId = (int)$masterIds[($offset + $scan) % $poolCount];
            $scan++;
            if (isset($used[$companyId])) continue;

            $terms = $this->offeringTerms((string)$country['currency_code'], $slot);
            $featured = $featuredCount < 5 ? 1 : 0;

            $db->transaction(function(Database $db) use ($companyId,$countryId,$country,$terms,$featured): void {
                $db->execute(
                    'INSERT IGNORE INTO company_country_assignments(company_id,country_id) VALUES (?,?)',
                    [$companyId,$countryId]
                );
                $db->execute(
                    'INSERT INTO investment_offerings(public_id,company_id,country_id,currency_code,share_price_minor,minimum_purchase_quantity,maximum_purchase_quantity,maximum_user_holding,total_available_quantity,fractional_shares_allowed,quantity_decimal_places,profit_type,projected_profit_value,fixed_profit_basis,duration_value,duration_unit,selling_enabled,is_active,is_featured,created_at,updated_at) VALUES (?,?,?,?,?,1,NULL,NULL,100000,0,0,"PERCENTAGE",?,NULL,?,"DAYS",0,1,?,NOW(),NOW())',
                    [$this->uuid(),$companyId,$countryId,$country['currency_code'],$terms['price_minor'],$terms['projected_return'],$terms['duration_days'],$featured]
                );
            });

            $used[$companyId] = true;
            $activeCount++;
            if ($featured) $featuredCount++;
            $slot++;
        }

        if ($activeCount < $target) {
            throw new \RuntimeException('Unable to seed the required catalogue size for ' . ($country['name'] ?? $country['slug']));
        }
    }

    private function offeringTerms(string $currency, int $slot): array
    {
        $returns = [7.50,8.25,9.00,9.75,10.50,11.25,8.50,9.50,10.25,11.00,12.00,8.75,9.25,10.00,10.75,11.50,7.75,8.80,9.80,10.80];
        $durations = [180,210,240,270,300,330,365,180,240,365,210,270,330,180,300,365,240,270,330,365];

        $price = match ($currency) {
            'KRW' => 50000 + ($slot * 9000),
            'JPY' => 5000 + ($slot * 900),
            default => 5000 + ($slot * 1750),
        };

        return [
            'price_minor' => $price,
            'projected_return' => $returns[$slot % count($returns)],
            'duration_days' => $durations[$slot % count($durations)],
        ];
    }

    private function companies(): array
    {
        return [
            ['key'=>'master-apple','legal'=>'Apple Inc.','name'=>'Apple','slug'=>'apple','ticker'=>'AAPL','exchange'=>'Nasdaq Global Select Market','mic'=>'XNAS','listing_country'=>'US','hq_country'=>'US','city'=>'Cupertino','sector'=>'Technology','industry'=>'Consumer electronics and software','website'=>'https://www.apple.com/','ir'=>'https://investor.apple.com/','description'=>'Technology company designing consumer devices, software and digital services.'],
            ['key'=>'master-microsoft','legal'=>'Microsoft Corporation','name'=>'Microsoft','slug'=>'microsoft','ticker'=>'MSFT','exchange'=>'Nasdaq Global Select Market','mic'=>'XNAS','listing_country'=>'US','hq_country'=>'US','city'=>'Redmond','sector'=>'Technology','industry'=>'Software and cloud computing','website'=>'https://www.microsoft.com/','ir'=>'https://www.microsoft.com/en-us/Investor','description'=>'Global software, cloud-computing and enterprise technology company.'],
            ['key'=>'master-nvidia','legal'=>'NVIDIA Corporation','name'=>'NVIDIA','slug'=>'nvidia','ticker'=>'NVDA','exchange'=>'Nasdaq Global Select Market','mic'=>'XNAS','listing_country'=>'US','hq_country'=>'US','city'=>'Santa Clara','sector'=>'Technology','industry'=>'Semiconductors and accelerated computing','website'=>'https://www.nvidia.com/','ir'=>'https://investor.nvidia.com/','description'=>'Semiconductor and computing company focused on accelerated computing and graphics technologies.'],
            ['key'=>'master-alphabet','legal'=>'Alphabet Inc.','name'=>'Alphabet','slug'=>'alphabet','ticker'=>'GOOGL','exchange'=>'Nasdaq Global Select Market','mic'=>'XNAS','listing_country'=>'US','hq_country'=>'US','city'=>'Mountain View','sector'=>'Communication Services','industry'=>'Internet services and technology','website'=>'https://abc.xyz/','ir'=>'https://abc.xyz/investor/','description'=>'Technology holding company whose businesses include Google and other internet and technology operations.'],
            ['key'=>'master-amazon','legal'=>'Amazon.com, Inc.','name'=>'Amazon','slug'=>'amazon','ticker'=>'AMZN','exchange'=>'Nasdaq Global Select Market','mic'=>'XNAS','listing_country'=>'US','hq_country'=>'US','city'=>'Seattle','sector'=>'Consumer Discretionary','industry'=>'E-commerce and cloud computing','website'=>'https://www.amazon.com/','ir'=>'https://ir.aboutamazon.com/','description'=>'Global e-commerce, logistics, digital services and cloud-computing company.'],
            ['key'=>'master-jpmorgan','legal'=>'JPMorgan Chase & Co.','name'=>'JPMorgan Chase','slug'=>'jpmorgan-chase','ticker'=>'JPM','exchange'=>'New York Stock Exchange','mic'=>'XNYS','listing_country'=>'US','hq_country'=>'US','city'=>'New York','sector'=>'Financials','industry'=>'Banking and financial services','website'=>'https://www.jpmorganchase.com/','ir'=>'https://www.jpmorganchase.com/ir','description'=>'Diversified global banking and financial-services company.'],
            ['key'=>'master-visa','legal'=>'Visa Inc.','name'=>'Visa','slug'=>'visa','ticker'=>'V','exchange'=>'New York Stock Exchange','mic'=>'XNYS','listing_country'=>'US','hq_country'=>'US','city'=>'San Francisco','sector'=>'Financials','industry'=>'Payments technology','website'=>'https://www.visa.com/','ir'=>'https://investor.visa.com/','description'=>'Global digital-payments technology company connecting consumers, merchants and financial institutions.'],
            ['key'=>'master-coca-cola','legal'=>'The Coca-Cola Company','name'=>'Coca-Cola','slug'=>'coca-cola','ticker'=>'KO','exchange'=>'New York Stock Exchange','mic'=>'XNYS','listing_country'=>'US','hq_country'=>'US','city'=>'Atlanta','sector'=>'Consumer Staples','industry'=>'Beverages','website'=>'https://www.coca-colacompany.com/','ir'=>'https://investors.coca-colacompany.com/','description'=>'Global beverage company with a portfolio of sparkling, water, juice and other drink brands.'],
            ['key'=>'master-sap','legal'=>'Meta Platforms, Inc.','name'=>'Meta Platforms','slug'=>'meta-platforms','ticker'=>'META','exchange'=>'Nasdaq Global Select Market','mic'=>'XNAS','listing_country'=>'US','hq_country'=>'US','city'=>'Menlo Park','sector'=>'Communication Services','industry'=>'Social media and digital advertising','website'=>'https://about.meta.com/','ir'=>'https://investor.atmeta.com/','description'=>'Technology company operating social platforms, messaging products and digital advertising services.'],
            ['key'=>'master-siemens','legal'=>'Tesla, Inc.','name'=>'Tesla','slug'=>'tesla','ticker'=>'TSLA','exchange'=>'Nasdaq Global Select Market','mic'=>'XNAS','listing_country'=>'US','hq_country'=>'US','city'=>'Austin','sector'=>'Consumer Discretionary','industry'=>'Electric vehicles and energy','website'=>'https://www.tesla.com/','ir'=>'https://ir.tesla.com/','description'=>'Electric vehicle, energy storage and clean-energy technology company.'],
            ['key'=>'master-asml','legal'=>'Broadcom Inc.','name'=>'Broadcom','slug'=>'broadcom','ticker'=>'AVGO','exchange'=>'Nasdaq Global Select Market','mic'=>'XNAS','listing_country'=>'US','hq_country'=>'US','city'=>'Palo Alto','sector'=>'Technology','industry'=>'Semiconductors and infrastructure software','website'=>'https://www.broadcom.com/','ir'=>'https://investors.broadcom.com/','description'=>'Technology company providing semiconductor and infrastructure software products.'],
            ['key'=>'master-lvmh','legal'=>'Berkshire Hathaway Inc.','name'=>'Berkshire Hathaway','slug'=>'berkshire-hathaway','ticker'=>'BRK.B','exchange'=>'New York Stock Exchange','mic'=>'XNYS','listing_country'=>'US','hq_country'=>'US','city'=>'Omaha','sector'=>'Financials','industry'=>'Diversified holding company','website'=>'https://www.berkshirehathaway.com/','ir'=>'https://www.berkshirehathaway.com/reports.html','description'=>'Diversified holding company with businesses across insurance, rail, energy, manufacturing and consumer sectors.'],
            ['key'=>'master-totalenergies','legal'=>'Exxon Mobil Corporation','name'=>'Exxon Mobil','slug'=>'exxon-mobil','ticker'=>'XOM','exchange'=>'New York Stock Exchange','mic'=>'XNYS','listing_country'=>'US','hq_country'=>'US','city'=>'Spring','sector'=>'Energy','industry'=>'Integrated energy','website'=>'https://corporate.exxonmobil.com/','ir'=>'https://investor.exxonmobil.com/','description'=>'Integrated energy company operating across oil, natural gas, fuels, chemicals and lower-emission technologies.'],
            ['key'=>'master-toyota','legal'=>'Walmart Inc.','name'=>'Walmart','slug'=>'walmart','ticker'=>'WMT','exchange'=>'Nasdaq Global Select Market','mic'=>'XNAS','listing_country'=>'US','hq_country'=>'US','city'=>'Bentonville','sector'=>'Consumer Staples','industry'=>'Retail','website'=>'https://corporate.walmart.com/','ir'=>'https://stock.walmart.com/','description'=>'Large-scale retail and e-commerce company serving consumers through stores, clubs and digital channels.'],
            ['key'=>'master-sony','legal'=>'Mastercard Incorporated','name'=>'Mastercard','slug'=>'mastercard','ticker'=>'MA','exchange'=>'New York Stock Exchange','mic'=>'XNYS','listing_country'=>'US','hq_country'=>'US','city'=>'Purchase','sector'=>'Financials','industry'=>'Payments technology','website'=>'https://www.mastercard.com/','ir'=>'https://investor.mastercard.com/','description'=>'Global payments technology company connecting consumers, businesses and financial institutions.'],
            ['key'=>'master-samsung','legal'=>'Eli Lilly and Company','name'=>'Eli Lilly','slug'=>'eli-lilly','ticker'=>'LLY','exchange'=>'New York Stock Exchange','mic'=>'XNYS','listing_country'=>'US','hq_country'=>'US','city'=>'Indianapolis','sector'=>'Health Care','industry'=>'Pharmaceuticals','website'=>'https://www.lilly.com/','ir'=>'https://investor.lilly.com/','description'=>'Global pharmaceutical company developing medicines across multiple therapeutic areas.'],
            ['key'=>'master-dbs','legal'=>'Oracle Corporation','name'=>'Oracle','slug'=>'oracle','ticker'=>'ORCL','exchange'=>'New York Stock Exchange','mic'=>'XNYS','listing_country'=>'US','hq_country'=>'US','city'=>'Austin','sector'=>'Technology','industry'=>'Enterprise software and cloud computing','website'=>'https://www.oracle.com/','ir'=>'https://investor.oracle.com/','description'=>'Enterprise software and cloud infrastructure company serving businesses and public-sector organizations.'],
            ['key'=>'master-hsbc','legal'=>'Costco Wholesale Corporation','name'=>'Costco Wholesale','slug'=>'costco-wholesale','ticker'=>'COST','exchange'=>'Nasdaq Global Select Market','mic'=>'XNAS','listing_country'=>'US','hq_country'=>'US','city'=>'Issaquah','sector'=>'Consumer Staples','industry'=>'Warehouse retail','website'=>'https://www.costco.com/','ir'=>'https://investor.costco.com/','description'=>'Membership warehouse retailer operating physical and digital retail channels.'],
            ['key'=>'master-bhp','legal'=>'Netflix, Inc.','name'=>'Netflix','slug'=>'netflix','ticker'=>'NFLX','exchange'=>'Nasdaq Global Select Market','mic'=>'XNAS','listing_country'=>'US','hq_country'=>'US','city'=>'Los Gatos','sector'=>'Communication Services','industry'=>'Streaming entertainment','website'=>'https://www.netflix.com/','ir'=>'https://ir.netflix.net/','description'=>'Entertainment company providing subscription streaming and related media services.'],
            ['key'=>'master-reliance','legal'=>'Advanced Micro Devices, Inc.','name'=>'AMD','slug'=>'amd','ticker'=>'AMD','exchange'=>'Nasdaq Global Select Market','mic'=>'XNAS','listing_country'=>'US','hq_country'=>'US','city'=>'Santa Clara','sector'=>'Technology','industry'=>'Semiconductors','website'=>'https://www.amd.com/','ir'=>'https://ir.amd.com/','description'=>'Semiconductor company designing processors, graphics products and adaptive computing technologies.'],
        ];
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20,12);
    }
}
