<?php
declare(strict_types=1);

namespace Database\Seeds;

use App\Support\Database;

final class WithdrawalFormSeeder
{
    public function run(Database $db): void
    {
        $db->execute(
            'INSERT INTO dynamic_forms(name,form_key,purpose,active,created_at,updated_at) VALUES (?,?,\'WITHDRAWAL\',1,NOW(),NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),purpose=\'WITHDRAWAL\',active=1,updated_at=NOW()',
            ['Standard Withdrawal Details','standard_withdrawal_details']
        );
        $formId=(int)$db->scalar("SELECT id FROM dynamic_forms WHERE form_key='standard_withdrawal_details' LIMIT 1");

        $fields=[
            ['payout_type','SELECT','Payout type','', 'Choose how you want to receive the withdrawal.',1,10],
            ['recipient_name','TEXT','Recipient / account holder name','Full legal name','Enter the name attached to the destination account or wallet owner.',1,20],
            ['institution_or_network','TEXT','Bank name or crypto network','e.g. Chase Bank, Binance Smart Chain','For bank transfer enter the bank or financial institution. For crypto enter the exact network.',1,30],
            ['account_or_wallet','TEXTAREA','Account / IBAN / wallet address','Enter the destination details','For bank transfer enter the account number or IBAN. For crypto enter the wallet address. Verify every character before submitting.',1,40],
            ['routing_code','TEXT','SWIFT / BIC / routing code','Optional','Enter a SWIFT, BIC, routing or sort code when your bank transfer requires one.',0,50],
            ['destination_country','TEXT','Destination country','Country of bank or payout destination','Optional. This can help the team review cross-border payout instructions.',0,60],
            ['additional_notes','TEXTAREA','Additional payout instructions','Optional notes','Add any other information needed to process the withdrawal. Do not include passwords or authentication codes.',0,70],
        ];

        foreach($fields as [$key,$type,$label,$placeholder,$help,$required,$order]){
            $db->execute(
                'INSERT INTO dynamic_form_fields(form_id,field_key,field_type,label,placeholder,help_text,is_required,is_user_editable,is_visible,sort_order,created_at,updated_at) VALUES (?,?,?,?,?,?,?,1,1,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE field_type=VALUES(field_type),label=VALUES(label),placeholder=VALUES(placeholder),help_text=VALUES(help_text),is_required=VALUES(is_required),is_user_editable=1,is_visible=1,sort_order=VALUES(sort_order),updated_at=NOW()',
                [$formId,$key,$type,$label,$placeholder?:null,$help?:null,$required,$order]
            );
        }

        $payoutFieldId=(int)$db->scalar('SELECT id FROM dynamic_form_fields WHERE form_id=? AND field_key=\'payout_type\' LIMIT 1',[$formId]);
        foreach([['bank_transfer','Bank transfer',10],['cryptocurrency','Cryptocurrency',20]] as [$value,$label,$order]){
            $db->execute(
                'INSERT INTO dynamic_form_field_options(field_id,option_value,option_label,is_active,sort_order) VALUES (?,?,?,1,?) ON DUPLICATE KEY UPDATE option_label=VALUES(option_label),is_active=1,sort_order=VALUES(sort_order)',
                [$payoutFieldId,$value,$label,$order]
            );
        }

        $method=$db->one("SELECT id FROM withdrawal_methods WHERE slug='standard-withdrawal' LIMIT 1");
        if($method){
            $methodId=(int)$method['id'];
            $db->execute(
                'UPDATE withdrawal_methods SET name=?,description=?,instructions=?,enabled=1,minimum_amount_minor=1,maximum_amount_minor=NULL,fee_type=\'NONE\',fee_value=0,fee_behavior=\'DEDUCT_FROM_REQUEST\',estimated_processing_time=?,form_id=?,sort_order=0,updated_at=NOW() WHERE id=?',
                ['Standard Withdrawal','Withdraw by bank transfer or supported cryptocurrency using one secure payout form.','Choose your payout type and enter the destination details carefully. Withdrawal requests are reviewed before payment. Incorrect bank, network or wallet information can delay or prevent recovery of a payout.','1–3 business days',$formId,$methodId]
            );
        }else{
            $db->execute(
                'INSERT INTO withdrawal_methods(public_id,name,slug,description,instructions,enabled,minimum_amount_minor,maximum_amount_minor,fee_type,fee_value,fee_behavior,estimated_processing_time,form_id,sort_order,created_at,updated_at) VALUES (?,?,?,?,?,1,1,NULL,\'NONE\',0,\'DEDUCT_FROM_REQUEST\',?,?,0,NOW(),NOW())',
                [$this->uuid(),'Standard Withdrawal','standard-withdrawal','Withdraw by bank transfer or supported cryptocurrency using one secure payout form.','Choose your payout type and enter the destination details carefully. Withdrawal requests are reviewed before payment. Incorrect bank, network or wallet information can delay or prevent recovery of a payout.','1–3 business days',$formId]
            );
            $methodId=(int)$db->pdo()->lastInsertId();
        }

        foreach($db->select('SELECT id FROM countries WHERE is_active=1 AND is_enabled=1') as $country){
            $db->execute(
                'INSERT INTO withdrawal_method_countries(withdrawal_method_id,country_id) VALUES (?,?) ON DUPLICATE KEY UPDATE withdrawal_method_id=VALUES(withdrawal_method_id)',
                [$methodId,(int)$country['id']]
            );
        }
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
