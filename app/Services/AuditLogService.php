<?php
declare(strict_types=1);
namespace App\Services;

use App\Support\{Database,Request};

final class AuditLogService {
    public function __construct(private ?Database $db) {}
    public function record(?int $actor,string $action,string $entityType,string|int|null $entityId,?array $old=null,?array $new=null,?string $reason=null,?Request $request=null,array $metadata=[]): void {
        if(!$this->db)return;
        $this->db->execute('INSERT INTO audit_logs(actor_user_id,action,entity_type,entity_id,old_values,new_values,metadata,reason,ip_address,user_agent,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())',[$actor,$action,$entityType,$entityId===null?null:(string)$entityId,$this->json($old),$this->json($new),$this->json($metadata),$reason,$request?->ip(),$request?->userAgent()]);
    }
    private function json(?array $value): ?string { if($value===null)return null; return json_encode($this->redact($value),JSON_THROW_ON_ERROR); }
    private function redact(array $value): array {
        $sensitive=['password','password_hash','token','secret','two_factor_secret_encrypted','recovery_code','mail_password','db_password','account_number','iban'];
        foreach($value as $k=>$v){ if(in_array(strtolower((string)$k),$sensitive,true))$value[$k]='[redacted]'; elseif(is_array($v))$value[$k]=$this->redact($v); }
        return $value;
    }
}
