<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Support\{Request,Response};

final class AdminMethodController extends Controller
{
    public function methods(Request $r): Response
    {
        $kind=$this->kind($r);
        $table=$kind==='withdrawal'?'withdrawal_methods':'deposit_methods';
        $rows=app('database')?->select('SELECT * FROM '.$table.' ORDER BY sort_order,name')??[];
        return $this->view('admin/methods',[
            'title'=>ucfirst($kind).' methods',
            'kind'=>$kind,
            'methods'=>$rows,
            'countries'=>app('countries')->allEnabled(),
        ],'layouts.admin');
    }

    public function saveMethod(Request $r): Response
    {
        $kind=$this->kind($r);
        if($kind==='deposit') return $this->createDepositMethod($r);

        try{
            $db=app('database');
            $slug=$this->slug((string)$r->input('slug'));
            $db?->transaction(function($db)use($r,$slug){
                $db->execute(
                    'INSERT INTO withdrawal_methods(public_id,name,slug,description,instructions,enabled,minimum_amount_minor,maximum_amount_minor,fee_type,fee_value,estimated_processing_time,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                    [bin2hex(random_bytes(16)),trim((string)$r->input('name')),$slug,trim((string)$r->input('description')),trim((string)$r->input('instructions')),$r->input('enabled')?1:0,(int)$r->input('minimum_amount_minor',0),$r->input('maximum_amount_minor')!==''?(int)$r->input('maximum_amount_minor'):null,$r->input('fee_type','NONE'),$r->input('fee_value','0'),trim((string)$r->input('processing_time')),0]
                );
                $id=(int)$db->pdo()->lastInsertId();
                foreach((array)$r->input('countries',[]) as $countryId){
                    $db->execute('INSERT INTO withdrawal_method_countries(withdrawal_method_id,country_id) VALUES (?,?)',[$id,(int)$countryId]);
                }
            });
            $this->flash('success','Withdrawal method created.');
        }catch(\Throwable $e){
            $this->flash('error',$e->getMessage());
        }
        return $this->redirect('admin.methods',['kind'=>$kind]);
    }

    public function editMethod(Request $r): Response
    {
        $kind=$this->kind($r);
        $method=$this->method($kind,(int)$r->route('method'));
        if(!$method) return new Response('Method not found.',404);

        $pivot=$kind==='withdrawal'?'withdrawal_method_countries':'deposit_method_countries';
        $fk=$kind==='withdrawal'?'withdrawal_method_id':'deposit_method_id';
        $assigned=array_map('intval',array_column(app('database')?->select('SELECT country_id FROM '.$pivot.' WHERE '.$fk.'=?',[$method['id']])??[],'country_id'));
        $fields=$method['form_id']?app('forms')->definition((int)$method['form_id']):[];
        $details=$kind==='deposit'&&$method['payment_details']?json_decode((string)$method['payment_details'],true):[];
        $qr=null;
        if($kind==='deposit'&&$method['method_type']==='CRYPTO'&&!empty($details['wallet_address'])){
            $qr=app('qr')->dataUri((string)$details['wallet_address']);
        }

        return $this->view('admin/method-edit',[
            'title'=>'Edit '.$method['name'],
            'kind'=>$kind,
            'method'=>$method,
            'countries'=>app('countries')->allEnabled(),
            'assignedCountries'=>$assigned,
            'fields'=>$fields,
            'details'=>is_array($details)?$details:[],
            'qr'=>$qr,
        ],'layouts.admin');
    }

    public function updateMethod(Request $r): Response
    {
        $kind=$this->kind($r);
        $method=$this->method($kind,(int)$r->route('method'));
        if(!$method) return $this->redirect('admin.methods',['kind'=>$kind]);

        try{
            if($kind==='deposit') $this->updateDeposit($r,$method);
            else $this->updateWithdrawal($r,$method);
            $this->flash('success','Deposit method updated.');
        }catch(\Throwable $e){
            $this->flash('error',$e->getMessage());
        }
        return Response::redirect(route('admin.methods.edit',['kind'=>$kind,'method'=>$method['id']]));
    }

    public function addMethodField(Request $r): Response
    {
        $kind=$this->kind($r);
        $method=$this->method($kind,(int)$r->route('method'));
        if(!$method||empty($method['form_id'])){
            $this->flash('error','This method does not have an editable form.');
            return $this->redirect('admin.methods',['kind'=>$kind]);
        }

        try{
            $type=strtoupper((string)$r->input('field_type','TEXT'));
            $allowed=['TEXT','NUMBER','EMAIL','PHONE','TEXTAREA','DATE','FILE'];
            if(!in_array($type,$allowed,true)) throw new \InvalidArgumentException('Choose a supported field type.');
            $label=trim((string)$r->input('label',''));
            if($label==='') throw new \InvalidArgumentException('Field label is required.');
            $base=strtolower(trim(preg_replace('/[^a-z0-9]+/i','_',$label)??'','_'))?:'field';
            $key=$base;$n=2;
            while((bool)app('database')?->scalar('SELECT COUNT(*) FROM dynamic_form_fields WHERE form_id=? AND field_key=?',[$method['form_id'],$key])) $key=$base.'_'.$n++;
            $sort=(int)(app('database')?->scalar('SELECT COALESCE(MAX(sort_order),0)+10 FROM dynamic_form_fields WHERE form_id=?',[$method['form_id']])??10);
            app('database')?->execute(
                'INSERT INTO dynamic_form_fields(form_id,field_key,field_type,label,placeholder,help_text,is_required,is_user_editable,is_visible,sort_order,max_file_size,created_at,updated_at) VALUES (?,?,?,?,?,?,?,1,1,?,CASE WHEN ?="FILE" THEN 20971520 ELSE NULL END,NOW(),NOW())',
                [(int)$method['form_id'],$key,$type,$label,trim((string)$r->input('placeholder',''))?:null,trim((string)$r->input('help_text',''))?:null,$r->input('required')?1:0,$sort,$type]
            );
            $this->flash('success','Field added.');
        }catch(\Throwable $e){
            $this->flash('error',$e->getMessage());
        }

        return Response::redirect(route('admin.methods.edit',['kind'=>$kind,'method'=>$method['id']]));
    }

    public function deleteMethodField(Request $r): Response
    {
        $kind=$this->kind($r);
        $method=$this->method($kind,(int)$r->route('method'));
        if($method&&$method['form_id']){
            app('database')?->execute(
                'DELETE FROM dynamic_form_fields WHERE id=? AND form_id=?',
                [(int)$r->route('field'),(int)$method['form_id']]
            );
            $this->flash('success','Field removed.');
        }
        return Response::redirect(route('admin.methods.edit',['kind'=>$kind,'method'=>(int)$r->route('method')]));
    }

    public function forms(Request $r): Response
    {
        $forms=app('database')?->select('SELECT * FROM dynamic_forms ORDER BY purpose,name')??[];
        return $this->view('admin/forms',['title'=>'Dynamic form builder','forms'=>$forms],'layouts.admin');
    }

    public function saveForm(Request $r): Response
    {
        try{
            $purpose=(string)$r->input('purpose');
            if(!in_array($purpose,['DEPOSIT','WITHDRAWAL','KYC','GENERAL'],true)) throw new \InvalidArgumentException('Invalid form purpose.');
            $key=strtolower(preg_replace('/[^a-z0-9_]+/','_',trim((string)$r->input('form_key')))??'');
            app('database')?->execute('INSERT INTO dynamic_forms(name,form_key,purpose,active) VALUES (?,?,?,1)',[trim((string)$r->input('name')),$key,$purpose]);
            $this->flash('success','Form created.');
        }catch(\Throwable $e){
            $this->flash('error',$e->getMessage());
        }
        return $this->redirect('admin.forms');
    }

    public function addField(Request $r): Response
    {
        try{
            $type=(string)$r->input('field_type');
            $allowed=['TEXT','NUMBER','EMAIL','PHONE','TEXTAREA','SELECT','MULTI_SELECT','RADIO','CHECKBOX','DATE','FILE','COPYABLE_VALUE','INSTRUCTION'];
            if(!in_array($type,$allowed,true)) throw new \InvalidArgumentException('Invalid field type.');
            $key=strtolower(preg_replace('/[^a-z0-9_]+/','_',trim((string)$r->input('field_key')))??'');
            app('database')?->execute('INSERT INTO dynamic_form_fields(form_id,field_key,field_type,label,placeholder,help_text,is_required,sort_order) VALUES (?,?,?,?,?,?,?,?)',[(int)$r->input('form_id'),$key,$type,trim((string)$r->input('label')),trim((string)$r->input('placeholder')),trim((string)$r->input('help_text')),$r->input('required')?1:0,(int)$r->input('sort_order',0)]);
            $this->flash('success','Field added.');
        }catch(\Throwable $e){
            $this->flash('error',$e->getMessage());
        }
        return $this->redirect('admin.forms');
    }

    private function createDepositMethod(Request $r): Response
    {
        try{
            $name=trim((string)$r->input('name',''));
            if($name==='') throw new \InvalidArgumentException('Method name is required.');
            $type=strtoupper((string)$r->input('method_type','CUSTOM'));
            if(!in_array($type,['BANK','CRYPTO','CUSTOM'],true)) throw new \InvalidArgumentException('Choose a valid method type.');
            $slug=$this->uniqueDepositSlug($name);
            $db=app('database');
            $id=$db?->transaction(function($db)use($name,$type,$slug,$r){
                $formKey='deposit_'.$slug.'_'.substr(bin2hex(random_bytes(3)),0,6);
                $db->execute('INSERT INTO dynamic_forms(name,form_key,purpose,active,created_at,updated_at) VALUES (?,?,\'DEPOSIT\',1,NOW(),NOW())',[$name.' Details',$formKey]);
                $formId=(int)$db->pdo()->lastInsertId();
                $db->execute(
                    'INSERT INTO deposit_methods(public_id,name,slug,method_type,description,instructions,payment_details,enabled,minimum_amount_minor,maximum_amount_minor,fee_type,fee_value,fee_behavior,estimated_processing_time,form_id,sort_order,created_at,updated_at) VALUES (?,?,?,?,?,?,?,0,0,NULL,\'NONE\',0,\'DEDUCT_FROM_CREDIT\',NULL,?,0,NOW(),NOW())',
                    [bin2hex(random_bytes(16)),$name,$slug,$type,'','',json_encode(new \stdClass(),JSON_THROW_ON_ERROR),$formId]
                );
                $methodId=(int)$db->pdo()->lastInsertId();
                $this->assignCountries($db,'deposit',$methodId,$r);
                return $methodId;
            });
            $this->flash('success','Deposit method created. Add its payment details and fields below.');
            return Response::redirect(route('admin.methods.edit',['kind'=>'deposit','method'=>$id]));
        }catch(\Throwable $e){
            $this->flash('error',$e->getMessage());
            return $this->redirect('admin.methods',['kind'=>'deposit']);
        }
    }

    private function updateDeposit(Request $r,array $method): void
    {
        $name=trim((string)$r->input('name',''));
        if($name==='') throw new \InvalidArgumentException('Method name is required.');
        $type=strtoupper((string)$r->input('method_type','CUSTOM'));
        if(!in_array($type,['BANK','CRYPTO','CUSTOM'],true)) throw new \InvalidArgumentException('Invalid method type.');

        $details=[];
        if($type==='BANK'){
            $details=[
                'beneficiary_name'=>trim((string)$r->input('beneficiary_name','')),
                'bank_name'=>trim((string)$r->input('bank_name','')),
                'account_iban'=>trim((string)$r->input('account_iban','')),
                'swift_bic'=>trim((string)$r->input('swift_bic','')),
                'routing_aba'=>trim((string)$r->input('routing_aba','')),
                'bank_address'=>trim((string)$r->input('bank_address','')),
                'beneficiary_address'=>trim((string)$r->input('beneficiary_address','')),
            ];
        }elseif($type==='CRYPTO'){
            $details=[
                'asset'=>strtoupper(trim((string)$r->input('asset',''))),
                'network'=>trim((string)$r->input('network','')),
                'wallet_address'=>trim((string)$r->input('wallet_address','')),
            ];
        }

        $enabled=$r->input('enabled')?1:0;
        if($enabled&&$type==='BANK'&&($details['beneficiary_name']===''||$details['bank_name']===''||$details['account_iban']===''||$details['swift_bic']==='')){
            throw new \InvalidArgumentException('To enable an international bank transfer, add beneficiary name, bank name, account/IBAN and SWIFT/BIC.');
        }
        if($enabled&&$type==='CRYPTO'&&($details['asset']===''||$details['network']===''||$details['wallet_address']==='')){
            throw new \InvalidArgumentException('To enable a crypto deposit, add the asset, network and wallet address.');
        }

        $feeType=strtoupper((string)$r->input('fee_type','NONE'));
        if(!in_array($feeType,['NONE','FIXED','PERCENTAGE'],true)) $feeType='NONE';

        $db=app('database');
        $db?->transaction(function($db)use($r,$method,$name,$type,$details,$enabled,$feeType){
            $db->execute(
                'UPDATE deposit_methods SET name=?,method_type=?,description=?,instructions=?,payment_details=?,enabled=?,minimum_amount_minor=?,maximum_amount_minor=?,fee_type=?,fee_value=?,estimated_processing_time=?,updated_at=NOW() WHERE id=?',
                [
                    $name,$type,trim((string)$r->input('description','')),trim((string)$r->input('instructions','')),
                    json_encode($details,JSON_THROW_ON_ERROR),$enabled,max(0,(int)$r->input('minimum_amount_minor',0)),
                    $r->input('maximum_amount_minor')!==''?max(0,(int)$r->input('maximum_amount_minor')):null,
                    $feeType,(string)$r->input('fee_value','0'),trim((string)$r->input('processing_time',''))?:null,(int)$method['id']
                ]
            );
            $this->assignCountries($db,'deposit',(int)$method['id'],$r,true);
        });
    }

    private function updateWithdrawal(Request $r,array $method): void
    {
        app('database')?->execute(
            'UPDATE withdrawal_methods SET name=?,description=?,instructions=?,enabled=?,estimated_processing_time=?,updated_at=NOW() WHERE id=?',
            [trim((string)$r->input('name')),trim((string)$r->input('description')),trim((string)$r->input('instructions')),$r->input('enabled')?1:0,trim((string)$r->input('processing_time')),(int)$method['id']]
        );
    }

    private function assignCountries($db,string $kind,int $methodId,Request $r,bool $replace=false): void
    {
        $pivot=$kind==='withdrawal'?'withdrawal_method_countries':'deposit_method_countries';
        $fk=$kind==='withdrawal'?'withdrawal_method_id':'deposit_method_id';
        if($replace) $db->execute('DELETE FROM '.$pivot.' WHERE '.$fk.'=?',[$methodId]);

        $ids=[];
        if($r->input('all_countries')){
            foreach(app('countries')->allEnabled() as $country) $ids[]=(int)$country['id'];
        }else{
            $ids=array_values(array_unique(array_map('intval',(array)$r->input('countries',[]))));
        }
        if(!$ids) throw new \InvalidArgumentException('Choose at least one Country Pack or select all countries.');
        foreach($ids as $countryId){
            $db->execute('INSERT IGNORE INTO '.$pivot.'('.$fk.',country_id) VALUES (?,?)',[$methodId,$countryId]);
        }
    }

    private function method(string $kind,int $id): ?array
    {
        $table=$kind==='withdrawal'?'withdrawal_methods':'deposit_methods';
        return app('database')?->one('SELECT * FROM '.$table.' WHERE id=?',[$id]);
    }

    private function kind(Request $r): string
    {
        $kind=(string)$r->route('kind');
        if(!in_array($kind,['deposit','withdrawal'],true)) throw new \InvalidArgumentException('Unknown method type.');
        return $kind;
    }

    private function slug(string $value): string
    {
        $slug=strtolower(trim($value));
        if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$slug)) throw new \InvalidArgumentException('Use a safe URL slug.');
        return $slug;
    }

    private function uniqueDepositSlug(string $name): string
    {
        $base=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$name)??'','-'))?:'deposit-method';
        $slug=$base;$n=2;
        while((bool)app('database')?->scalar('SELECT COUNT(*) FROM deposit_methods WHERE slug=?',[$slug])) $slug=$base.'-'.$n++;
        return $slug;
    }
}
