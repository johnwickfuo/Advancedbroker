<?php
declare(strict_types=1);
namespace App\Services;
use App\Support\Database;

final class NotificationService {
    public function __construct(private ?Database $db) {}

    public function create(int $userId,string $type,string $title,string $body='',array $data=[],int $displayLimit=1): void {
        $displayLimit=max(1,min($displayLimit,100));
        $this->db?->execute(
            'INSERT INTO notifications (user_id,channel,notification_type,title,body,data,display_limit,display_count,created_at) VALUES (?,"popup",?,?,?,?,?,0,NOW())',
            [$userId,$type,$title,$body,json_encode($data,JSON_THROW_ON_ERROR),$displayLimit]
        );
    }

    public function transactional(int $userId,string $type,string $title,string $body='',array $data=[]): void {
        $this->create($userId,$type,$title,$body,$data);
        $this->sendTransactionalEmail($userId,$type,$title,$body,$data);
    }

    private function sendTransactionalEmail(int $userId,string $type,string $title,string $body,array $data): void {
        if(!$this->db) return;
        $user=$this->db->one('SELECT id,email,first_name FROM users WHERE id=? LIMIT 1',[$userId]);
        if(!$user||!filter_var((string)$user['email'],FILTER_VALIDATE_EMAIL)) return;
        $target='';
        if(!empty($data['url'])&&is_string($data['url'])){
            $target=str_starts_with($data['url'],'http://')||str_starts_with($data['url'],'https://')?$data['url']:url($data['url']);
        }
        $status=strtolower((string)config('mail.driver','smtp'))==='log'?'LOGGED':'SENT';
        try{
            app('mail')->send(
                (string)$user['email'],
                $title.' · '.config('app.name'),
                'transactional-notification',
                ['name'=>(string)($user['first_name']??''),'heading'=>$title,'message'=>$body,'url'=>$target]
            );
            try{
                $this->db->execute(
                    'INSERT INTO email_delivery_logs(user_id,template_key,recipient,delivery_status,related_entity_type,related_entity_id,sent_at,created_at) VALUES (?,?,?,?,?,?,NOW(),NOW())',
                    [$userId,'notification.'.$type,(string)$user['email'],$status,'notification',$type]
                );
            }catch(\Throwable){/* Email logging is best-effort. */}
        }catch(\Throwable $e){
            try{
                $this->db->execute(
                    'INSERT INTO email_delivery_logs(user_id,template_key,recipient,delivery_status,related_entity_type,related_entity_id,failure_reason,created_at) VALUES (?,?,?,?,?,?,?,NOW())',
                    [$userId,'notification.'.$type,(string)$user['email'],'FAILED','notification',$type,mb_substr($e->getMessage(),0,500)]
                );
            }catch(\Throwable){/* Email logging is best-effort. */}
            try{app('logger')->error('Transactional email failed',['user_id'=>$userId,'type'=>$type,'message'=>$e->getMessage()]);}catch(\Throwable){}
        }
    }

    public function consumePopups(int $userId,int $limit=6): array {
        if(!$this->db) return [];
        return $this->db->transaction(function(Database $db)use($userId,$limit){
            $rows=$db->select(
                'SELECT * FROM notifications WHERE user_id=? AND channel="popup" AND display_count<display_limit ORDER BY created_at ASC LIMIT '.max(1,min($limit,20)),
                [$userId]
            );
            foreach($rows as &$row){
                $next=min((int)$row['display_limit'],(int)$row['display_count']+1);
                $db->execute(
                    'UPDATE notifications SET display_count=?,read_at=CASE WHEN ?>=display_limit THEN COALESCE(read_at,NOW()) ELSE read_at END WHERE id=? AND user_id=?',
                    [$next,$next,$row['id'],$userId]
                );
                $row['display_count']=$next;
            }
            unset($row);
            return $rows;
        });
    }

    public function activeAdminPopups(int $userId): array {
        return $this->db?->select(
            'SELECT * FROM notifications WHERE user_id=? AND notification_type="admin_popup" AND display_count<display_limit ORDER BY created_at DESC',
            [$userId]
        )??[];
    }
    public function endAdminPopup(int $userId,int $notificationId): bool {
        if(!$this->db)return false;
        $count=$this->db->execute(
            'UPDATE notifications SET display_count=display_limit,read_at=COALESCE(read_at,NOW()) WHERE id=? AND user_id=? AND notification_type="admin_popup" AND display_count<display_limit',
            [$notificationId,$userId]
        );
        return $count>0;
    }
    public function recent(int $userId,int $limit=8): array { return $this->db?->select('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT '.(int)$limit,[$userId])??[]; }
    public function unreadCount(int $userId): int { return (int)($this->db?->scalar('SELECT COUNT(*) FROM notifications WHERE user_id=? AND channel="popup" AND read_at IS NULL',[$userId])??0); }
    public function markRead(int $userId,int $id): void { $this->db?->execute('UPDATE notifications SET read_at=COALESCE(read_at,NOW()) WHERE id=? AND user_id=?',[$id,$userId]); }
    public function markAllRead(int $userId): void { $this->db?->execute('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL',[$userId]); }
}
