<?php
declare(strict_types=1);
namespace App\Services;
use App\Support\{Database,Logger,Request};
final class SecurityEventService {
    public function __construct(private ?Database $db,private Logger $logger) {}
    public function record(?int $userId,string $type,?Request $request=null,array $metadata=[]): void { unset($metadata['password'],$metadata['token'],$metadata['secret'],$metadata['code']); $this->db?->execute('INSERT INTO security_events (user_id,event_type,ip_address,user_agent,metadata,created_at) VALUES (?,?,?,?,?,NOW())',[$userId,$type,$request?->ip(),$request?->userAgent(),json_encode($metadata,JSON_THROW_ON_ERROR)]); $this->logger->security($type,['user_id'=>$userId,'ip'=>$request?->ip()]); }
    public function recent(int $userId,int $limit=12): array { return $this->db?->select('SELECT * FROM security_events WHERE user_id=? ORDER BY created_at DESC LIMIT '.(int)$limit,[$userId])??[]; }
}
