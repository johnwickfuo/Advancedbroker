<?php
declare(strict_types=1);

namespace Database\Seeds;

use App\Support\Database;

final class PlatformContentSeeder
{
    private const VERSION='apextrades-2026-09-25';

    public function run(Database $db): void
    {
        $languageId=(int)$db->scalar("SELECT id FROM languages WHERE code='en' AND active=1 LIMIT 1");
        if($languageId<1) throw new \RuntimeException('English language must be seeded before platform content.');

        $countries=$db->select('SELECT id FROM countries WHERE is_active=1 AND is_enabled=1 ORDER BY id');
        foreach($countries as $country){
            foreach($this->legalDocuments() as $type=>$document){
                $existing=$db->one(
                    'SELECT id FROM legal_documents WHERE country_id=? AND language_id=? AND document_type=? AND version=? LIMIT 1',
                    [(int)$country['id'],$languageId,$type,self::VERSION]
                );
                if($existing){
                    $db->execute(
                        'UPDATE legal_documents SET title=?,body=?,is_published=1,published_at=COALESCE(published_at,NOW()),effective_at=COALESCE(effective_at,NOW()),updated_at=NOW() WHERE id=?',
                        [$document['title'],$document['body'],(int)$existing['id']]
                    );
                    continue;
                }
                $db->execute(
                    'INSERT INTO legal_documents(country_id,language_id,document_type,title,body,version,is_published,published_at,effective_at,created_by_user_id,created_at,updated_at) VALUES (?,?,?,?,?,?,1,NOW(),NOW(),NULL,NOW(),NOW())',
                    [(int)$country['id'],$languageId,$type,$document['title'],$document['body'],self::VERSION]
                );
            }
        }

        foreach($this->faqs() as $index=>$faq){
            $existing=$db->one(
                'SELECT id FROM faqs WHERE country_id IS NULL AND language_id=? AND question=? LIMIT 1',
                [$languageId,$faq['question']]
            );
            if($existing){
                $db->execute(
                    'UPDATE faqs SET category=?,answer=?,sort_order=?,is_active=1,updated_at=NOW() WHERE id=?',
                    [$faq['category'],$faq['answer'],$index+1,(int)$existing['id']]
                );
            }else{
                $db->execute(
                    'INSERT INTO faqs(country_id,language_id,category,question,answer,sort_order,is_active,created_at,updated_at) VALUES (NULL,?,?,?,?,?,1,NOW(),NOW())',
                    [$languageId,$faq['category'],$faq['question'],$faq['answer'],$index+1]
                );
            }
        }
    }

    private function legalDocuments(): array
    {
        return [
            'terms'=>[
                'title'=>'ApexTrades Terms of Service',
                'body'=><<<'HTML'
<h2>1. About these terms</h2>
<p>These Terms of Service govern access to and use of ApexTrades, including the website, account dashboard, investment features, AI trading features, wallet records, deposits, withdrawals and related support services. By creating or using an account, you agree to these terms and to any country-specific disclosures shown to you.</p>
<h2>2. Eligibility and account information</h2>
<p>You must provide accurate, current and complete information and keep it updated. You are responsible for protecting your password, authentication codes and devices. You must promptly contact support if you believe your account has been accessed without permission.</p>
<h2>3. Country and currency context</h2>
<p>ApexTrades may display different currencies, products, payment methods, content or requirements based on the Country Pack assigned to your account. An administrator-assigned country may take priority over location detection. Changing your physical location or IP address does not necessarily change the country assigned to an authenticated account.</p>
<h2>4. Deposits and wallet balances</h2>
<p>Deposit requests may require manual review and supporting information before funds are credited. A submitted deposit does not increase your available balance until it is approved and recorded. You are responsible for following the payment instructions shown for the selected method and for using accurate payment references.</p>
<h2>5. Investments and AI trading products</h2>
<p>Before confirming a transaction, review the price, duration, quantity, projected or fixed-profit terms, fees and other information displayed for that product. Existing purchases may retain the terms recorded at the time of purchase even if later offers change. Product availability may differ by country and may be suspended or withdrawn.</p>
<h2>6. Withdrawals and verification</h2>
<p>Withdrawals are subject to available balance, identity or compliance checks where required, accurate payout details and administrative review. Funds may be reserved while a withdrawal request is pending. Incorrect payout information can cause delays, rejection or loss after payment has been sent to the destination supplied by the user.</p>
<h2>7. No personal financial advice</h2>
<p>Information on ApexTrades is provided for platform and product information. It is not personalised financial, tax, legal or accounting advice. You are responsible for deciding whether a product is suitable for you and for obtaining independent advice where appropriate.</p>
<h2>8. Prohibited use</h2>
<p>You may not use ApexTrades for fraud, money laundering, sanctions evasion, unlawful transactions, impersonation, abusive automated access, interference with platform security or any activity that violates applicable law. We may restrict or suspend accounts when reasonably necessary to protect users, the platform or comply with legal obligations.</p>
<h2>9. Service availability</h2>
<p>We aim to keep the service available, but access can be interrupted by maintenance, network failures, third-party services, security incidents or events outside our reasonable control. Features may be changed, paused or discontinued where operational, legal or security needs require it.</p>
<h2>10. Risk and responsibility</h2>
<p>Using investment and trading-related services involves financial and operational risk. You should read the Risk Disclosure before committing funds. Nothing in these terms removes rights or protections that cannot lawfully be excluded in your jurisdiction.</p>
<h2>11. Changes to these terms</h2>
<p>We may publish a new version of these terms when the service, applicable requirements or business processes change. Where appropriate, material changes may require renewed acceptance before certain account actions are available.</p>
<h2>12. Contact</h2>
<p>Questions about these terms should be sent through the contact details shown on the ApexTrades website for your Country Pack.</p>
HTML
            ],
            'privacy'=>[
                'title'=>'ApexTrades Privacy Policy',
                'body'=><<<'HTML'
<h2>1. Information we collect</h2>
<p>ApexTrades may collect information you provide directly, including your name, contact details, account credentials, profile information, payout details, transaction instructions, support messages and documents submitted for identity verification or payment review.</p>
<h2>2. Account and technical information</h2>
<p>We may also process account identifiers, login and security events, IP addresses, device and browser information, session records, language preferences, Country Pack assignment, approximate country information used for market resolution and records needed to protect the service from abuse.</p>
<h2>3. Financial and transaction records</h2>
<p>We maintain records of wallet movements, deposits, investments, AI trading purchases, withdrawals, fees, references and administrative actions. Financial ledger records may need to be retained for security, reconciliation, legal, accounting and dispute-resolution purposes.</p>
<h2>4. How information is used</h2>
<p>We use personal information to create and secure accounts, provide requested services, process transactions, perform verification, communicate with users, prevent fraud, investigate incidents, maintain audit trails, improve reliability and comply with applicable obligations.</p>
<h2>5. Service providers and required disclosures</h2>
<p>Information may be shared with service providers that help operate hosting, email, payment, security, identity verification or other platform functions, subject to appropriate safeguards. Information may also be disclosed when required by law, lawful process, regulatory obligation or to protect the rights and security of users or the service.</p>
<h2>6. Cookies and sessions</h2>
<p>ApexTrades uses cookies and similar session technologies to keep users signed in, protect forms, remember authorised sessions and support account security. Disabling essential cookies may prevent authenticated features from working correctly.</p>
<h2>7. Data security</h2>
<p>We use technical and organisational measures intended to protect personal information, including access controls, password hashing, protected sessions and restricted storage for private documents. No internet service can guarantee absolute security, so users should also protect their credentials and devices.</p>
<h2>8. Retention</h2>
<p>Information is retained for as long as needed to provide the service, maintain legitimate business and security records, resolve disputes and meet applicable legal or regulatory requirements. Different categories of information may have different retention periods.</p>
<h2>9. Your choices and rights</h2>
<p>Depending on your location, you may have rights to request access, correction, deletion, restriction, objection, portability or other treatment of your personal information. Some requests may be limited where records must be retained for security, financial, legal or compliance reasons.</p>
<h2>10. International processing</h2>
<p>Because ApexTrades may serve users in multiple countries, information may be processed or stored in jurisdictions different from your own. Where required, appropriate safeguards should be used for cross-border processing.</p>
<h2>11. Policy updates and contact</h2>
<p>This policy may be updated as the service or applicable requirements change. Privacy questions or requests should be sent through the support contact shown on the ApexTrades website.</p>
HTML
            ],
            'risk-disclosure'=>[
                'title'=>'ApexTrades Risk Disclosure',
                'body'=><<<'HTML'
<h2>Read this before committing funds</h2>
<p>Investment and trading-related activity involves risk. You should only commit funds after reviewing the product terms and considering your financial situation, objectives and ability to bear loss or delayed access to funds.</p>
<h2>Market and issuer risk</h2>
<p>The value and performance of companies, securities, currencies and other assets can be affected by economic conditions, business performance, regulation, liquidity and market events. Historical information does not guarantee future results.</p>
<h2>Projected and fixed-profit terms</h2>
<p>Some ApexTrades products may display projected returns or fixed-profit terms for a defined platform product. Those figures describe the terms shown for that product; they do not make external markets risk-free and do not remove operational, counterparty, liquidity, legal or platform risk. Review the exact terms recorded before confirming a purchase.</p>
<h2>Liquidity and timing risk</h2>
<p>Funds committed to an investment or timed product may not be immediately withdrawable. Sale requests, maturity processing, withdrawals and manual reviews can take time, and some transactions may be unavailable while an account or country service is restricted.</p>
<h2>Currency risk</h2>
<p>If a product uses a base currency different from the currency displayed for your Country Pack, exchange-rate assumptions or snapshots may be used. Currency values can change, and a stored conversion rate for an existing transaction may differ from a later market rate.</p>
<h2>Technology and operational risk</h2>
<p>Online services can experience outages, software defects, cyberattacks, communication failures, data errors or third-party service disruption. Security controls reduce risk but cannot eliminate every possible failure.</p>
<h2>Account and payout risk</h2>
<p>You are responsible for protecting your account and providing correct payment and withdrawal information. Payments sent to an incorrect bank account, wallet address or network supplied by a user may be difficult or impossible to recover.</p>
<h2>Legal and regulatory risk</h2>
<p>Rules affecting investment, payments, identity verification, taxation and financial services vary by jurisdiction and can change. Product availability or account functionality may therefore differ by country or change over time.</p>
<h2>No guarantee of suitability</h2>
<p>A product being available on ApexTrades does not mean it is suitable for every user. Consider independent professional advice where needed and do not use money required for essential living expenses or obligations.</p>
HTML
            ],
        ];
    }

    private function faqs(): array
    {
        return [
            ['category'=>'Accounts','question'=>'How does ApexTrades choose my investment market?','answer'=>'Guests are matched to a supported Country Pack using available location information. Unsupported locations use the Global version. After sign-in, the country assigned to your account takes priority over later IP or VPN changes.'],
            ['category'=>'Accounts','question'=>'Can I change my account country myself?','answer'=>'Country assignment is controlled by the platform because it affects currency, product availability, payment methods and compliance settings. Contact support if your account has been assigned to the wrong market.'],
            ['category'=>'Deposits','question'=>'When does a deposit appear in my wallet?','answer'=>'A deposit request does not change your wallet balance immediately. Funds are credited only after the request and any required proof have been reviewed and approved.'],
            ['category'=>'Investments','question'=>'Do investment terms change after I purchase?','answer'=>'An existing position keeps the important price, quantity, duration and return terms recorded when the purchase was created. Later edits to an offer do not rewrite an existing position.'],
            ['category'=>'AI Trading','question'=>'How do AI trading codes work?','answer'=>'Choose an available category, purchase a unique code and activate it from your dashboard. The category price, return terms and duration are recorded for your purchase, and the timed cycle begins after successful activation.'],
            ['category'=>'AI Trading','question'=>'Can an AI trading code be used more than once?','answer'=>'No. Each activation code is unique and single-use. Once it has been successfully activated for its purchase, it cannot be activated again.'],
            ['category'=>'Withdrawals','question'=>'Why can a withdrawal be unavailable?','answer'=>'Withdrawals can depend on available wallet balance, account status, identity-verification requirements and whether a withdrawal method is enabled for your Country Pack.'],
            ['category'=>'Withdrawals','question'=>'What happens to my balance while a withdrawal is pending?','answer'=>'The amount required for the withdrawal is reserved while the request is reviewed. It is no longer spendable, but it is not recorded as a completed withdrawal until the payout process is completed.'],
            ['category'=>'Security','question'=>'How can I protect my ApexTrades account?','answer'=>'Use a password you do not reuse elsewhere, protect access to your email and devices, enable two-factor authentication where available, and never share authentication or recovery codes.'],
            ['category'=>'Security','question'=>'What should I do if I notice an unfamiliar login or transaction?','answer'=>'Change your password, review active sessions, revoke unfamiliar sessions and contact support as soon as possible with the relevant account or transaction details.'],
            ['category'=>'Support','question'=>'How will ApexTrades contact me about my account?','answer'=>'Account notices may appear in your dashboard and may also be sent to the email address registered to your account using the platform email system.'],
            ['category'=>'Risk','question'=>'Are all investments risk-free?','answer'=>'No. Investment and trading-related activity can involve market, liquidity, operational, counterparty, currency and legal risks. Review the Risk Disclosure and the specific product terms before committing funds.'],
        ];
    }
}
