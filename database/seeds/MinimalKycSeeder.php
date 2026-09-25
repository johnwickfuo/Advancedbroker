<?php
declare(strict_types=1);

namespace Database\Seeds;

use App\Support\Database;

final class MinimalKycSeeder
{
    public function run(Database $db): void
    {
        $formKey='apextrades_minimal_kyc';
        $db->execute(
            'INSERT INTO dynamic_forms(name,form_key,purpose,active,created_at,updated_at) VALUES (?,?,"KYC",1,NOW(),NOW()) ON DUPLICATE KEY UPDATE name=VALUES(name),active=1,updated_at=NOW()',
            ['ApexTrades Minimal KYC',$formKey]
        );
        $formId=(int)$db->scalar('SELECT id FROM dynamic_forms WHERE form_key=? LIMIT 1',[$formKey]);

        $fields=[
            ['legal_full_name','TEXT','Full legal name','As shown on your government ID','Enter your full legal name exactly as it appears on your identification document.',1,10,null],
            ['date_of_birth','DATE','Date of birth','','',1,20,null],
            ['residential_address','TEXTAREA','Residential address','Full current residential address','Include street, city/town, state/province and postal code where applicable.',1,30,null],
            ['country_of_residence','TEXT','Country of residence','','',1,40,null],
            ['identity_document_type','SELECT','Identity document type','','Choose the government-issued document you are submitting.',1,50,null],
            ['identity_document_number','TEXT','Identity document number','Document / passport number','Enter the number exactly as it appears on the document.',1,60,null],
            ['identity_document','FILE','Government-issued ID','','Upload a clear image or PDF of your valid identification document.',1,70,json_encode(['image/jpeg','image/png','application/pdf'],JSON_THROW_ON_ERROR)],
        ];

        foreach($fields as [$key,$type,$label,$placeholder,$help,$required,$order,$mimes]){
            $db->execute(
                'INSERT INTO dynamic_form_fields(form_id,field_key,field_type,label,placeholder,help_text,accepted_mimes,max_file_size,is_required,is_user_editable,is_visible,sort_order,created_at,updated_at) VALUES (?,?,?,?,?,?,?,CASE WHEN ?="FILE" THEN 20971520 ELSE NULL END,?,1,1,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE field_type=VALUES(field_type),label=VALUES(label),placeholder=VALUES(placeholder),help_text=VALUES(help_text),accepted_mimes=VALUES(accepted_mimes),max_file_size=VALUES(max_file_size),is_required=VALUES(is_required),is_user_editable=1,is_visible=1,sort_order=VALUES(sort_order),updated_at=NOW()',
                [$formId,$key,$type,$label,$placeholder?:null,$help?:null,$mimes,$type,$required,$order]
            );
        }

        $typeFieldId=(int)$db->scalar(
            'SELECT id FROM dynamic_form_fields WHERE form_id=? AND field_key="identity_document_type" LIMIT 1',
            [$formId]
        );
        foreach([
            ['passport','Passport',10],
            ['national_id','National identity card',20],
            ['drivers_license','Driver\'s licence',30],
        ] as [$value,$label,$order]){
            $db->execute(
                'INSERT INTO dynamic_form_field_options(field_id,option_value,option_label,is_active,sort_order) VALUES (?,?,?,1,?) ON DUPLICATE KEY UPDATE option_label=VALUES(option_label),is_active=1,sort_order=VALUES(sort_order)',
                [$typeFieldId,$value,$label,$order]
            );
        }

        $countries=$db->select('SELECT id FROM countries WHERE is_global=1 OR (is_active=1 AND is_enabled=1) ORDER BY id');
        foreach($countries as $country){
            $countryId=(int)$country['id'];
            $active=(int)$db->scalar(
                'SELECT COUNT(*) FROM kyc_configurations WHERE country_id=? AND is_active=1',
                [$countryId]
            );
            if($active>0) continue;

            $version=(int)($db->scalar('SELECT COALESCE(MAX(version),0)+1 FROM kyc_configurations WHERE country_id=?',[$countryId])??1);
            $db->execute(
                'INSERT INTO kyc_configurations(country_id,form_id,is_required,instructions,version,is_active,created_at,updated_at) VALUES (?,?,0,?,?,1,NOW(),NOW())',
                [
                    $countryId,
                    $formId,
                    'Verify your identity by providing the basic information below and one valid government-issued identification document. Make sure the document is clear, current and matches the information entered on the form.',
                    $version,
                ]
            );
        }
    }
}
